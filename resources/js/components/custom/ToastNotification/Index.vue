<script setup lang="ts">
import { ref, onMounted } from 'vue'

const props = defineProps<{
  message: string
  type?: 'success' | 'error' | 'info'
  duration?: number
}>()

const visible = ref(false)

onMounted(() => {
  visible.value = true
  setTimeout(() => {
    visible.value = false
  }, props.duration ?? 3000) // 3 seconds default
})

// const bgColor = () => {
//   switch (props.type) {
//     case 'success': return 'bg-green-600 text-white'
//     case 'error': return 'bg-red-600 text-white'
//     case 'info': return 'bg-blue-600 text-white'
//     default: return 'bg-gray-800 text-white'
//   }
// }

const bgColor = () => {
  const t = props.type ?? 'success' // default fallback
  switch (t) {
    case 'success': return 'bg-green-600 text-white'
    case 'error': return 'bg-red-600 text-white'
    case 'info': return 'bg-blue-600 text-white'
    default: return 'bg-gray-800 text-white'
  }
}
</script>

<template>
  <transition name="fade">
    <div
      v-if="visible"
      :class="['fixed bottom-5 right-5 px-4 py-2 rounded shadow-lg', bgColor()]"
    >
      {{ message }}
    </div>
  </transition>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.3s ease;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}
</style>
