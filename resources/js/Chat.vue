<template>
  <div class="fixed bottom-6 right-6 z-50">
    <!-- Chat Button -->
    <transition name="fade">
      <button
        v-if="!isOpen"
        @click="toggleChat"
        class="bg-gradient-to-r from-blue-600 to-indigo-600 text-white p-4 rounded-full shadow-lg hover:shadow-xl transition-all duration-200 hover:scale-110 group"
      >
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
        </svg>
        <span class="absolute -top-8 right-0 bg-gray-900 text-white text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap">
          Ask about Sigmie
        </span>
      </button>
    </transition>

    <!-- Chat Window -->
    <transition name="slide-up">
      <div
        v-if="isOpen"
        class="bg-white dark:bg-gray-900 rounded-2xl shadow-2xl w-96 h-[600px] flex flex-col border border-gray-200 dark:border-gray-800"
      >
        <!-- Header -->
        <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-800">
          <div class="flex items-center space-x-3">
            <div class="w-8 h-8 bg-gradient-to-r from-blue-600 to-indigo-600 rounded-full flex items-center justify-center">
              <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
              </svg>
            </div>
            <div>
              <h3 class="font-semibold text-gray-900 dark:text-white">Sigmie Assistant</h3>
              <p class="text-xs text-gray-500 dark:text-gray-400">Ask me anything about Sigmie</p>
            </div>
          </div>
          <button
            @click="toggleChat"
            class="text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300 transition-colors"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Messages -->
        <div ref="messagesContainer" class="flex-1 overflow-y-auto p-4 space-y-4">
          <!-- Welcome Message -->
          <div v-if="messages.length === 0" class="text-center py-8">
            <div class="w-16 h-16 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center mx-auto mb-4">
              <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
              </svg>
            </div>
            <h4 class="font-medium text-gray-900 dark:text-white mb-2">How can I help you?</h4>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Ask me anything about using Sigmie</p>
            
            <!-- Suggested Questions -->
            <div class="space-y-2">
              <button
                v-for="question in suggestedQuestions"
                :key="question"
                @click="askQuestion(question)"
                class="block w-full text-left px-3 py-2 bg-gray-50 dark:bg-gray-800 rounded-lg text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
              >
                {{ question }}
              </button>
            </div>
          </div>

          <!-- Chat Messages -->
          <div v-for="message in messages" :key="message.id" class="flex" :class="message.role === 'user' ? 'justify-end' : 'justify-start'">
            <div class="max-w-[85%]">
              <div 
                class="rounded-lg px-4 py-2"
                :class="message.role === 'user' 
                  ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white' 
                  : 'bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-white'"
              >
                <!-- Loading indicator for assistant messages -->
                <div v-if="message.role === 'assistant' && message.content === ''" class="flex space-x-2">
                  <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0ms"></div>
                  <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 150ms"></div>
                  <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 300ms"></div>
                </div>
                
                <!-- Message content with markdown support -->
                <div v-else v-html="renderMarkdown(message.content)" class="prose prose-sm dark:prose-invert max-w-none"></div>
              </div>
              <p class="text-xs text-gray-400 dark:text-gray-500 mt-1 px-1">
                {{ formatTime(message.timestamp) }}
              </p>
            </div>
          </div>
        </div>

        <!-- Input Area -->
        <div class="border-t border-gray-200 dark:border-gray-800 p-4">
          <form @submit.prevent="sendMessage" class="flex space-x-2">
            <input
              v-model="inputMessage"
              :disabled="isLoading"
              type="text"
              placeholder="Type your question..."
              class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg focus:outline-none focus:border-blue-500 dark:bg-gray-800 dark:text-white"
            />
            <button
              type="submit"
              :disabled="!inputMessage.trim() || isLoading"
              class="px-4 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-lg hover:from-blue-700 hover:to-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all"
            >
              <svg v-if="!isLoading" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
              </svg>
              <svg v-else class="w-5 h-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
            </button>
          </form>
          <p class="text-xs text-gray-400 dark:text-gray-500 mt-2 text-center">
            Powered by Sigmie RAG
          </p>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, nextTick, onMounted, onUnmounted } from 'vue'
import { marked } from 'marked'
import DOMPurify from 'dompurify'

const isOpen = ref(false)
const messages = ref([])
const inputMessage = ref('')
const isLoading = ref(false)
const messagesContainer = ref(null)

const suggestedQuestions = [
  'How do I perform semantic search?',
  'What is RAG in Sigmie?',
  'How to create an index with mappings?',
  'How do I integrate with Laravel Scout?'
]

// Initialize marked with options
marked.setOptions({
  breaks: true,
  gfm: true,
  highlight: function(code, lang) {
    return code
  }
})

const toggleChat = () => {
  isOpen.value = !isOpen.value
  if (isOpen.value && messages.value.length === 0) {
    // You can add a welcome message here if needed
  }
}

const askQuestion = (question) => {
  inputMessage.value = question
  sendMessage()
}

const sendMessage = async () => {
  if (!inputMessage.value.trim() || isLoading.value) return
  
  const userMessage = {
    id: Date.now(),
    role: 'user',
    content: inputMessage.value,
    timestamp: new Date()
  }
  
  messages.value.push(userMessage)
  const question = inputMessage.value
  inputMessage.value = ''
  
  // Add assistant message placeholder
  const assistantMessage = {
    id: Date.now() + 1,
    role: 'assistant',
    content: '',
    timestamp: new Date()
  }
  messages.value.push(assistantMessage)
  
  isLoading.value = true
  await scrollToBottom()
  
  try {
    const response = await fetch('/api/chat', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      body: JSON.stringify({ question })
    })
    
    if (!response.ok) throw new Error('Failed to get response')
    
    const data = await response.json()
    assistantMessage.content = data.answer
    assistantMessage.timestamp = new Date()
  } catch (error) {
    console.error('Chat error:', error)
    assistantMessage.content = 'Sorry, I encountered an error while processing your question. Please try again.'
  } finally {
    isLoading.value = false
    await scrollToBottom()
  }
}

const renderMarkdown = (content) => {
  if (!content) return ''
  const html = marked(content)
  return DOMPurify.sanitize(html)
}

const formatTime = (timestamp) => {
  const date = new Date(timestamp)
  return date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })
}

const scrollToBottom = async () => {
  await nextTick()
  if (messagesContainer.value) {
    messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight
  }
}

// Handle escape key
const handleEscape = (e) => {
  if (e.key === 'Escape' && isOpen.value) {
    toggleChat()
  }
}

onMounted(() => {
  document.addEventListener('keydown', handleEscape)
})

onUnmounted(() => {
  document.removeEventListener('keydown', handleEscape)
})
</script>

<style scoped>
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.3s;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}

.slide-up-enter-active, .slide-up-leave-active {
  transition: all 0.3s ease;
}
.slide-up-enter-from, .slide-up-leave-to {
  transform: translateY(20px);
  opacity: 0;
}

/* Prose customization for chat */
:deep(.prose) {
  font-size: 0.875rem;
}
:deep(.prose pre) {
  margin: 0.5rem 0;
  padding: 0.5rem;
  font-size: 0.75rem;
}
:deep(.prose code) {
  font-size: 0.75rem;
}
:deep(.prose h1, .prose h2, .prose h3) {
  margin-top: 0.75rem;
  margin-bottom: 0.5rem;
}
:deep(.prose p) {
  margin-top: 0.5rem;
  margin-bottom: 0.5rem;
}
</style>