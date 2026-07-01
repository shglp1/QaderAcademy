<template>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <!-- Hero Section -->
    <div class="text-center py-16 bg-gradient-to-r from-indigo-600 to-purple-600 rounded-lg mb-12 text-white">
      <h1 class="text-4xl font-bold mb-4">{{ $t('home.heroTitle') }}</h1>
      <p class="text-xl mb-8 max-w-3xl mx-auto">{{ $t('home.heroSubtitle') }}</p>
      <router-link to="/courses" class="bg-white text-indigo-600 px-8 py-3 rounded-md font-semibold hover:bg-gray-100 inline-block">
        {{ $t('home.browseCourses') }}
      </router-link>
    </div>

    <!-- Features -->
    <div class="grid md:grid-cols-3 gap-8 mb-12">
      <div class="bg-white p-6 rounded-lg shadow-sm text-center">
        <div class="text-indigo-600 text-4xl mb-4">👨‍🏫</div>
        <h3 class="text-lg font-semibold mb-2">{{ $t('home.features.expertTrainers') }}</h3>
        <p class="text-gray-600">Learn from industry experts and experienced educators</p>
      </div>
      <div class="bg-white p-6 rounded-lg shadow-sm text-center">
        <div class="text-indigo-600 text-4xl mb-4">⏰</div>
        <h3 class="text-lg font-semibold mb-2">{{ $t('home.features.flexibleLearning') }}</h3>
        <p class="text-gray-600">Study at your own pace, anytime and anywhere</p>
      </div>
      <div class="bg-white p-6 rounded-lg shadow-sm text-center">
        <div class="text-indigo-600 text-4xl mb-4">📜</div>
        <h3 class="text-lg font-semibold mb-2">{{ $t('home.features.certificates') }}</h3>
        <p class="text-gray-600">Earn recognized certificates upon completion</p>
      </div>
    </div>
  </div>
</template>

<script setup>
// Home view - landing page content
</script>
