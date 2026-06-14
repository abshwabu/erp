<script lang="ts">
import { defineComponent, h, type PropType } from 'vue'
import type { Component } from 'vue'

export default defineComponent({
  name: 'UiIcon',
  inheritAttrs: false,
  props: {
    icon: {
      type: [Object, Function] as PropType<Component>,
      required: true,
    },
    size: {
      type: [Number, String],
      default: 24,
    },
  },
  setup(props, { attrs }) {
    return () => h(props.icon, { ...attrs, size: props.size })
  },
})
</script>
