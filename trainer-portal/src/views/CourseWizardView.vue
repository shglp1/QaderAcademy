<template>
  <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-8">
      <button @click="goBack" class="text-gray-600 hover:text-gray-900 mb-4 flex items-center">
        <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
        </svg>
        {{ $t('common.back') }}
      </button>
      <h1 class="text-2xl font-bold text-gray-900">{{ $t('courseWizard.title') }}</h1>
    </div>

    <!-- Wizard Steps -->
    <div class="mb-8">
      <div class="flex items-center justify-between">
        <div v-for="(step, index) in steps" :key="index" 
             :class="['wizard-step', getStepClass(index)]"
             @click="canNavigateTo(index) && goToStep(index)">
          <div :class="['step-circle', index <= currentStep ? 'bg-indigo-600 text-white' : 'bg-white text-gray-500']">
            {{ index + 1 }}
          </div>
          <span class="ml-2 text-sm font-medium text-gray-700 hidden sm:block">{{ step }}</span>
          <div v-if="index < steps.length - 1" class="flex-1 h-0.5 mx-4" :class="index < currentStep ? 'bg-green-500' : 'bg-gray-200'"></div>
        </div>
      </div>
    </div>

    <!-- Step Content -->
    <div class="bg-white shadow rounded-lg p-6">
      <!-- Step 1: Course Info -->
      <div v-if="currentStep === 0">
        <h2 class="text-lg font-medium text-gray-900 mb-4">{{ $t('courseWizard.courseInfo.title') }}</h2>
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700">{{ $t('courseWizard.courseInfo.titleEn') }}</label>
            <input v-model="course.title_en" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border p-2" required />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">{{ $t('courseWizard.courseInfo.titleAr') }}</label>
            <input v-model="course.title_ar" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border p-2" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">{{ $t('courseWizard.courseInfo.description') }}</label>
            <textarea v-model="course.description_en" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border p-2"></textarea>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">{{ $t('courseWizard.courseInfo.descriptionAr') }}</label>
            <textarea v-model="course.description_ar" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border p-2"></textarea>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700">{{ $t('courseWizard.courseInfo.category') }}</label>
              <select v-model="course.category_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border p-2">
                <option value="">Select Category</option>
                <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">{{ $t('courseWizard.courseInfo.price') }}</label>
              <input v-model.number="course.price" type="number" step="0.01" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border p-2" />
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">{{ $t('courseWizard.courseInfo.duration') }}</label>
            <input v-model.number="course.duration" type="number" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border p-2" />
          </div>
        </div>
      </div>

      <!-- Step 2: Chapters -->
      <div v-if="currentStep === 1">
        <h2 class="text-lg font-medium text-gray-900 mb-4">{{ $t('courseWizard.chapters.title') }}</h2>
        <div class="space-y-4 mb-4">
          <div v-for="(chapter, idx) in chapters" :key="idx" class="border rounded-lg p-4 bg-gray-50">
            <div class="flex justify-between items-start mb-2">
              <span class="font-medium">Chapter {{ idx + 1 }}</span>
              <button @click="removeChapter(idx)" class="text-red-600 hover:text-red-800 text-sm">Remove</button>
            </div>
            <input v-model="chapter.title_en" placeholder="Chapter title (EN)" class="w-full mb-2 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border p-2" />
            <input v-model="chapter.title_ar" placeholder="عنوان الفصل (AR)" class="w-full mb-2 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border p-2" />
            <textarea v-model="chapter.description_en" placeholder="Description (optional)" rows="2" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border p-2"></textarea>
          </div>
        </div>
        <button @click="addChapter" class="w-full py-2 border-2 border-dashed border-gray-300 rounded-lg text-gray-600 hover:border-indigo-500 hover:text-indigo-600">
          + {{ $t('courseWizard.chapters.addChapter') }}
        </button>
      </div>

      <!-- Step 3: Videos -->
      <div v-if="currentStep === 2">
        <h2 class="text-lg font-medium text-gray-900 mb-4">{{ $t('courseWizard.videos.title') }}</h2>
        <div class="space-y-4 mb-4">
          <div v-for="(video, idx) in videos" :key="idx" class="border rounded-lg p-4 bg-gray-50">
            <div class="flex justify-between items-start mb-2">
              <span class="font-medium">Video {{ idx + 1 }}</span>
              <button @click="removeVideo(idx)" class="text-red-600 hover:text-red-800 text-sm">Remove</button>
            </div>
            <select v-model="video.chapter_index" class="w-full mb-2 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border p-2">
              <option value="" disabled>{{ $t('courseWizard.videos.selectChapter') }}</option>
              <option v-for="(ch, chIdx) in chapters" :key="chIdx" :value="chIdx">Chapter {{ chIdx + 1 }} - {{ ch.title_en }}</option>
            </select>
            <input v-model="video.title_en" placeholder="Video title (EN)" class="w-full mb-2 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border p-2" />
            <input v-model="video.video_url" placeholder="Video URL (YouTube/Vimeo/etc.)" class="w-full mb-2 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border p-2" />
            <div class="flex items-center gap-4">
              <input v-model.number="video.duration" type="number" placeholder="Duration (minutes)" class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border p-2" />
              <label class="flex items-center">
                <input v-model="video.is_intro" type="checkbox" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                <span class="ml-2 text-sm text-gray-700">{{ $t('courseWizard.videos.isIntro') }}</span>
              </label>
            </div>
          </div>
        </div>
        <button @click="addVideo" class="w-full py-2 border-2 border-dashed border-gray-300 rounded-lg text-gray-600 hover:border-indigo-500 hover:text-indigo-600">
          + {{ $t('courseWizard.videos.addVideo') }}
        </button>
      </div>

      <!-- Step 4: Quizzes -->
      <div v-if="currentStep === 3">
        <h2 class="text-lg font-medium text-gray-900 mb-4">{{ $t('courseWizard.quizzes.title') }}</h2>
        <div class="space-y-4 mb-4">
          <div v-for="(quiz, qIdx) in quizzes" :key="qIdx" class="border rounded-lg p-4 bg-gray-50">
            <div class="flex justify-between items-start mb-3">
              <span class="font-medium">Quiz {{ qIdx + 1 }}</span>
              <button @click="removeQuiz(qIdx)" class="text-red-600 hover:text-red-800 text-sm">Remove</button>
            </div>
            <select v-model="quiz.chapter_index" class="w-full mb-2 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border p-2">
              <option value="" disabled>{{ $t('courseWizard.videos.selectChapter') }}</option>
              <option v-for="(ch, chIdx) in chapters" :key="chIdx" :value="chIdx">Chapter {{ chIdx + 1 }} - {{ ch.title_en }}</option>
            </select>
            <input v-model="quiz.title_en" placeholder="Quiz title" class="w-full mb-2 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border p-2" />
            <div class="mb-2">
              <label class="block text-sm text-gray-700 mb-1">{{ $t('courseWizard.quizzes.passingScore') }}</label>
              <input v-model.number="quiz.passing_score" type="number" min="0" max="100" class="w-32 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border p-2" />
            </div>
            
            <!-- Quiz Questions -->
            <div class="mt-3 pl-4 border-l-2 border-indigo-200">
              <h4 class="text-sm font-medium text-gray-700 mb-2">{{ $t('courseWizard.quizzes.addQuestion') }}</h4>
              <div v-for="(question, qnIdx) in quiz.questions" :key="qnIdx" class="mb-3 pb-3 border-b last:border-0">
                <div class="flex justify-between mb-2">
                  <span class="text-xs text-gray-500">Question {{ qnIdx + 1 }}</span>
                  <button @click="removeQuestion(qIdx, qnIdx)" class="text-red-600 text-xs">Remove</button>
                </div>
                <select v-model="question.type" class="w-full mb-2 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border p-2 text-sm">
                  <option value="mcq">{{ $t('courseWizard.quizzes.mcq') }}</option>
                  <option value="written">{{ $t('courseWizard.quizzes.written') }}</option>
                </select>
                <textarea v-model="question.text" placeholder="Question text" rows="2" class="w-full mb-2 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border p-2 text-sm"></textarea>
                
                <div v-if="question.type === 'mcq'" class="space-y-2">
                  <div v-for="(opt, optIdx) in question.options" :key="optIdx" class="flex items-center gap-2">
                    <input type="radio" :name="'correct-' + qIdx + '-' + qnIdx" :value="optIdx" v-model="question.correct_answer" class="text-indigo-600 focus:ring-indigo-500" />
                    <input v-model="question.options[optIdx]" placeholder="Option {{ optIdx + 1 }}" class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border p-2 text-sm" />
                  </div>
                </div>
                <div v-else>
                  <textarea v-model="question.model_answer" placeholder="Model answer for grading" rows="2" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border p-2 text-sm"></textarea>
                </div>
              </div>
              <button @click="addQuestion(qIdx)" class="text-sm text-indigo-600 hover:text-indigo-800">+ Add Question</button>
            </div>
          </div>
        </div>
        <button @click="addQuiz" class="w-full py-2 border-2 border-dashed border-gray-300 rounded-lg text-gray-600 hover:border-indigo-500 hover:text-indigo-600">
          + {{ $t('courseWizard.quizzes.addQuiz') }}
        </button>
      </div>

      <!-- Step 5: Final Exam -->
      <div v-if="currentStep === 4">
        <h2 class="text-lg font-medium text-gray-900 mb-4">{{ $t('courseWizard.finalExam.title') }}</h2>
        <div class="border rounded-lg p-4 bg-gray-50 mb-4">
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('courseWizard.finalExam.passingScore') }}</label>
            <input v-model.number="finalExam.passing_score" type="number" min="0" max="100" class="w-32 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border p-2" />
          </div>
          
          <h4 class="text-sm font-medium text-gray-700 mb-2">{{ $t('courseWizard.finalExam.addQuestion') }}</h4>
          <div v-for="(question, idx) in finalExam.questions" :key="idx" class="mb-3 pb-3 border-b last:border-0">
            <div class="flex justify-between mb-2">
              <span class="text-xs text-gray-500">Question {{ idx + 1 }}</span>
              <button @click="removeFinalExamQuestion(idx)" class="text-red-600 text-xs">Remove</button>
            </div>
            <select v-model="question.type" class="w-full mb-2 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border p-2 text-sm">
              <option value="mcq">{{ $t('courseWizard.quizzes.mcq') }}</option>
              <option value="written">{{ $t('courseWizard.quizzes.written') }}</option>
            </select>
            <textarea v-model="question.text" placeholder="Question text" rows="2" class="w-full mb-2 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border p-2 text-sm"></textarea>
            
            <div v-if="question.type === 'mcq'" class="space-y-2">
              <div v-for="(opt, optIdx) in question.options" :key="optIdx" class="flex items-center gap-2">
                <input type="radio" :name="'exam-correct-' + idx" :value="optIdx" v-model="question.correct_answer" class="text-indigo-600 focus:ring-indigo-500" />
                <input v-model="question.options[optIdx]" placeholder="Option {{ optIdx + 1 }}" class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border p-2 text-sm" />
              </div>
            </div>
            <div v-else>
              <textarea v-model="question.model_answer" placeholder="Model answer for grading" rows="2" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border p-2 text-sm"></textarea>
            </div>
          </div>
          <button @click="addFinalExamQuestion" class="text-sm text-indigo-600 hover:text-indigo-800">+ Add Question</button>
        </div>
      </div>
    </div>

    <!-- Navigation Buttons -->
    <div class="mt-6 flex justify-between">
      <button v-if="currentStep > 0" @click="prevStep" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
        {{ $t('common.previous') }}
      </button>
      <div class="flex-1"></div>
      <button v-if="currentStep < steps.length - 1" @click="nextStep" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
        {{ $t('common.next') }}
      </button>
      <button v-else @click="submitCourse" :disabled="submitting" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 disabled:opacity-50">
        {{ submitting ? $t('common.loading') : $t('courseWizard.submitForApproval') }}
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import axios from 'axios'

const router = useRouter()
const route = useRoute()

const steps = [
  'courseWizard.step1',
  'courseWizard.step2',
  'courseWizard.step3',
  'courseWizard.step4',
  'courseWizard.step5'
]

const currentStep = ref(0)
const submitting = ref(false)
const categories = ref([])

const course = ref({
  title_en: '',
  title_ar: '',
  description_en: '',
  description_ar: '',
  category_id: '',
  price: 0,
  duration: null
})

const chapters = ref([])
const videos = ref([])
const quizzes = ref([])
const finalExam = ref({
  passing_score: 50,
  questions: []
})

const addChapter = () => {
  chapters.value.push({
    title_en: '',
    title_ar: '',
    description_en: ''
  })
}

const removeChapter = (idx) => {
  chapters.value.splice(idx, 1)
}

const addVideo = () => {
  videos.value.push({
    chapter_index: '',
    title_en: '',
    video_url: '',
    duration: null,
    is_intro: false
  })
}

const removeVideo = (idx) => {
  videos.value.splice(idx, 1)
}

const addQuiz = () => {
  quizzes.value.push({
    chapter_index: '',
    title_en: '',
    passing_score: 70,
    questions: []
  })
}

const removeQuiz = (idx) => {
  quizzes.value.splice(idx, 1)
}

const addQuestion = (quizIdx) => {
  quizzes.value[quizIdx].questions.push({
    type: 'mcq',
    text: '',
    options: ['', '', '', ''],
    correct_answer: 0,
    model_answer: ''
  })
}

const removeQuestion = (quizIdx, qIdx) => {
  quizzes.value[quizIdx].questions.splice(qIdx, 1)
}

const addFinalExamQuestion = () => {
  finalExam.value.questions.push({
    type: 'mcq',
    text: '',
    options: ['', '', '', ''],
    correct_answer: 0,
    model_answer: ''
  })
}

const removeFinalExamQuestion = (idx) => {
  finalExam.value.questions.splice(idx, 1)
}

const getStepClass = (index) => {
  if (index < currentStep.value) return 'completed'
  if (index === currentStep.value) return 'active'
  return ''
}

const canNavigateTo = (index) => {
  return index <= currentStep.value + 1
}

const goToStep = (index) => {
  if (canNavigateTo(index)) {
    currentStep.value = index
  }
}

const prevStep = () => {
  if (currentStep.value > 0) {
    currentStep.value--
  }
}

const nextStep = () => {
  if (currentStep.value < steps.length - 1) {
    currentStep.value++
  }
}

const goBack = () => {
  router.push('/courses')
}

const submitCourse = async () => {
  submitting.value = true
  
  try {
    // Step 1: Create course
    const courseRes = await axios.post('/api/trainer/courses', {
      title: course.value.title_en,
      title_ar: course.value.title_ar,
      description: course.value.description_en,
      description_ar: course.value.description_ar,
      category_id: parseInt(course.value.category_id),
      price: parseFloat(course.value.price),
      duration: course.value.duration
    })

    const courseId = courseRes.data.course.id

    // Step 2: Create chapters
    const createdChapters = []
    for (const chapter of chapters.value) {
      const res = await axios.post('/api/trainer/chapters', {
        course_id: courseId,
        title_en: chapter.title_en,
        title_ar: chapter.title_ar,
        description_en: chapter.description_en
      })
      createdChapters.push(res.data.chapter)
    }

    // Step 3: Create videos
    for (const video of videos.value) {
      if (createdChapters[video.chapter_index]) {
        await axios.post('/api/trainer/videos', {
          chapter_id: createdChapters[video.chapter_index].id,
          title_en: video.title_en,
          video_url: video.video_url,
          duration: video.duration,
          is_intro: video.is_intro
        })
      }
    }

    // Step 4: Create quizzes and questions
    for (const quiz of quizzes.value) {
      if (createdChapters[quiz.chapter_index]) {
        const quizRes = await axios.post('/api/trainer/quizzes', {
          chapter_id: createdChapters[quiz.chapter_index].id,
          title_en: quiz.title_en,
          passing_score: quiz.passing_score
        })
        
        const quizId = quizRes.data.quiz.id
        
        for (const question of quiz.questions) {
          await axios.post('/api/trainer/quiz-questions', {
            quiz_id: quizId,
            type: question.type,
            text: question.text,
            options: question.type === 'mcq' ? question.options : null,
            correct_answer: question.type === 'mcq' ? question.correct_answer : null,
            model_answer: question.type === 'written' ? question.model_answer : null
          })
        }
      }
    }

    // Step 5: Create final exam
    const examRes = await axios.post(`/api/trainer/final-exams/${courseId}`)
    const examId = examRes.data.exam.id
    
    for (const question of finalExam.value.questions) {
      await axios.post('/api/trainer/final-exam-questions', {
        final_exam_id: examId,
        type: question.type,
        text: question.text,
        options: question.type === 'mcq' ? question.options : null,
        correct_answer: question.type === 'mcq' ? question.correct_answer : null,
        model_answer: question.type === 'written' ? question.model_answer : null
      })
    }

    // Submit for approval
    await axios.post(`/api/trainer/courses/${courseId}/submit-for-approval`)

    alert('Course created and submitted for approval!')
    router.push('/courses')
    
  } catch (error) {
    console.error('Error creating course:', error)
    alert(error.response?.data?.message || 'Failed to create course')
  } finally {
    submitting.value = false
  }
}

onMounted(async () => {
  try {
    const res = await axios.get('/api/admin/categories')
    categories.value = res.data.categories?.data || []
  } catch (error) {
    console.error('Failed to load categories:', error)
  }
  
  // If editing, load existing course data
  if (route.params.id) {
    // TODO: Implement edit mode
  } else {
    // Initialize with one chapter
    addChapter()
  }
})
</script>
