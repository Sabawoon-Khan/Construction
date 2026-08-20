import { describe, expect, it } from 'vitest';
import { bindOfflineUser } from '@/offline/db';
import { listOfflineResource, putCacheEntry } from '@/offline/repository';
import {
    registeredOfflineResources,
    setOfflineSettingsCache,
} from '@/offline/settings';

describe('generic offline resources', () => {
    it('lists any resource key from the cache', async () => {
        await bindOfflineUser(31);

        await putCacheEntry({
            resource: 'demo_items',
            key: 'item-1',
            payload: { client_uuid: 'item-1', name: 'Alpha' },
            pendingLocal: true,
            ttlSeconds: null,
        });

        const items = await listOfflineResource('demo_items');

        expect(items).toHaveLength(1);
        expect(items[0]?.name).toBe('Alpha');
        expect(items[0]?.pending_local).toBe(true);
    });

    it('resolves warm/resources lists from settings for pullAllResources', () => {
        setOfflineSettingsCache({
            resources: ['field_reports', 'demo_items'],
            warm_resources: ['demo_items'],
        });

        expect(registeredOfflineResources()).toEqual(['demo_items']);

        setOfflineSettingsCache({
            resources: ['field_reports', 'demo_items'],
            warm_resources: [],
        });

        expect(registeredOfflineResources()).toEqual(['field_reports', 'demo_items']);
    });
});
