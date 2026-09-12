<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { DialogClose } from "reka-ui"
import { useTranslations } from "@/composables/useTranslations"
import { cn } from "@/lib/utils"
import { Button } from '@/components/ui/button'

const props = withDefaults(defineProps<{
  class?: HTMLAttributes["class"]
  showCloseButton?: boolean
}>(), {
  showCloseButton: false,
})

const { t } = useTranslations()
</script>

<template>
  <div
    data-slot="dialog-footer"
    :class="cn(
      'sticky bottom-0 z-10 -mx-4 mt-2 flex flex-col-reverse gap-2 border-t border-border/70 bg-background/95 px-4 pt-3 pb-[max(0.75rem,env(safe-area-inset-bottom))] backdrop-blur-sm',
      'sm:static sm:mx-0 sm:mt-0 sm:flex-row sm:justify-end sm:border-0 sm:bg-transparent sm:p-0 sm:backdrop-blur-none',
      props.class,
    )"
  >
    <slot />
    <DialogClose v-if="showCloseButton" as-child>
      <Button variant="outline">
        {{ t('Close') }}
      </Button>
    </DialogClose>
  </div>
</template>
