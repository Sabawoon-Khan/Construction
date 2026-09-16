<script setup lang="ts">
import type { DialogContentEmits, DialogContentProps } from "reka-ui"
import type { HTMLAttributes } from "vue"
import { X } from "@lucide/vue"
import { reactiveOmit } from "@vueuse/core"
import {
  DialogClose,
  DialogContent,
  DialogPortal,
  useForwardPropsEmits,
} from "reka-ui"
import { preventOutsideDismissForDatePicker } from "@/components/ui/date-picker"
import { useTranslations } from "@/composables/useTranslations"
import { cn } from "@/lib/utils"
import DialogOverlay from "./DialogOverlay.vue"

defineOptions({
  inheritAttrs: false,
})

const props = withDefaults(defineProps<DialogContentProps & { class?: HTMLAttributes["class"], showCloseButton?: boolean }>(), {
  showCloseButton: true,
})
const emits = defineEmits<DialogContentEmits>()

const { t } = useTranslations()
const delegatedProps = reactiveOmit(props, "class")

const forwarded = useForwardPropsEmits(delegatedProps, emits)
</script>

<template>
  <DialogPortal>
    <DialogOverlay />
    <DialogContent
      data-slot="dialog-content"
      v-bind="{ ...$attrs, ...forwarded }"
      @pointer-down-outside="preventOutsideDismissForDatePicker"
      @focus-outside="preventOutsideDismissForDatePicker"
      @interact-outside="preventOutsideDismissForDatePicker"
      :class="
        cn(
          'bg-background data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 data-[state=closed]:zoom-out-95 data-[state=open]:zoom-in-95 fixed z-50 grid w-full gap-4 border shadow-lg duration-200',
          'inset-x-0 bottom-0 max-h-[min(92dvh,100%)] translate-x-0 translate-y-0 overflow-y-auto overscroll-contain rounded-t-2xl p-4 pt-3 pb-[max(1rem,env(safe-area-inset-bottom))]',
          'sm:inset-auto sm:top-[50%] sm:left-[50%] sm:max-h-[min(90dvh,100%)] sm:w-full sm:max-w-lg sm:translate-x-[-50%] sm:translate-y-[-50%] sm:rounded-lg sm:p-6 sm:pt-6 sm:pb-6',
          props.class,
        )"
    >
      <div
        class="mx-auto mb-1 h-1 w-10 shrink-0 rounded-full bg-muted-foreground/35 sm:hidden"
        aria-hidden="true"
      />

      <slot />

      <DialogClose
        v-if="showCloseButton"
        data-slot="dialog-close"
        class="ring-offset-background focus:ring-ring data-[state=open]:bg-accent data-[state=open]:text-muted-foreground absolute top-3 end-3 rounded-xs opacity-70 transition-opacity hover:opacity-100 focus:ring-2 focus:ring-offset-2 focus:outline-hidden disabled:pointer-events-none sm:top-4 sm:end-4 [&_svg]:pointer-events-none [&_svg]:shrink-0 [&_svg:not([class*='size-'])]:size-4"
      >
        <X />
        <span class="sr-only">{{ t('Close') }}</span>
      </DialogClose>
    </DialogContent>
  </DialogPortal>
</template>
