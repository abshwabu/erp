<template>
  <div>
    <h3 class="text-xl font-semibold text-slate-900 mb-6 text-center">Forgot Password</h3>
    <p class="text-sm text-slate-500 mb-6 text-center">
      Enter your email address and we'll send you a link to reset your password.
    </p>
    <form @submit.prevent class="space-y-6">
      <UiInput label="Email address" type="email" placeholder="admin@example.com" required />
      <UiButton type="submit" class="w-full">Send Reset Link</UiButton>
      <div class="text-center">
        <router-link to="/login" class="text-sm font-medium text-primary-600 hover:text-primary-500">
          Back to login
        </router-link>
      </div>
    </form>
  </div>
</template>
