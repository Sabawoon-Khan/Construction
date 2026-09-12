import { mount } from '@vue/test-utils';
import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { defineComponent, h, nextTick } from 'vue';
import { beforeAll, describe, expect, it } from 'vitest';
import V2FilterBar from '@/components/v2/V2FilterBar.vue';
import V2TablePanel from '@/components/v2/V2TablePanel.vue';
import {
    indexTableColumn,
    useProvidedTableColumns,
} from '@/composables/useTableColumns';

const columns = [
    indexTableColumn(),
    { key: 'code', label: 'Code' },
    { key: 'name', label: 'Name' },
    { key: 'actions', label: 'Actions', locked: true },
];

describe('V2TablePanel column hide', () => {
    beforeAll(() => {
        const css = readFileSync(
            resolve(__dirname, '../../../../css/table-col-hide.css'),
            'utf8',
        );
        const style = document.createElement('style');
        style.textContent = css;
        document.head.appendChild(style);
    });

    it('updates hide classes when columnsState.toggle is called', async () => {
        const wrapper = mount(V2TablePanel, {
            props: { tableId: 'spec-panel-2', columns },
            slots: {
                default:
                    '<table><thead><tr><th>#</th><th>Code</th><th>Name</th><th>A</th></tr></thead></table>',
            },
            attachTo: document.body,
        });

        const panel = wrapper.vm as unknown as {
            columnsState: {
                toggle: (k: string) => void;
                isVisible: (k: string) => boolean;
            };
        };

        expect(panel.columnsState.isVisible('name')).toBe(true);
        panel.columnsState.toggle('name');
        await wrapper.vm.$nextTick();

        expect(panel.columnsState.isVisible('name')).toBe(false);
        expect(wrapper.find('.table-wrap').classes()).toContain('tf-hide-3');
        wrapper.unmount();
    });

    it('filters-slot inject toggles the same state and CSS hides the column', async () => {
        const Probe = defineComponent({
            setup() {
                const state = useProvidedTableColumns();
                return () =>
                    h(
                        'button',
                        {
                            id: 'probe',
                            onClick: () => state?.setVisible('name', false),
                        },
                        String(state?.isVisible('name')),
                    );
            },
        });

        const wrapper = mount(V2TablePanel, {
            props: { tableId: 'spec-panel-3', columns },
            slots: {
                filters: () => h(V2FilterBar, null, { columns: () => h(Probe) }),
                default:
                    '<table><thead><tr><th>#</th><th>Code</th><th>Name</th><th>A</th></tr></thead><tbody><tr><td>1</td><td>P</td><td>N</td><td>x</td></tr></tbody></table>',
            },
            attachTo: document.body,
        });

        const probe = wrapper.find('#probe');
        expect(probe.exists()).toBe(true);
        expect(probe.text()).toBe('true');
        await probe.trigger('click');
        await nextTick();
        expect(wrapper.find('#probe').text()).toBe('false');
        expect(wrapper.find('.table-wrap').classes()).toContain('tf-hide-3');

        const nameHeader = wrapper.find('thead tr th:nth-child(3)')
            .element as HTMLElement;
        const nameCell = wrapper.find('tbody tr td:nth-child(3)')
            .element as HTMLElement;
        expect(getComputedStyle(nameHeader).display).toBe('none');
        expect(getComputedStyle(nameCell).display).toBe('none');
        wrapper.unmount();
    });
});
