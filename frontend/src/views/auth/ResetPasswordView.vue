<template>
  <div>
    <h3 class="text-xl font-semibold text-slate-900 mb-6 text-center">Reset Password</h3>
    <form @submit.prevent class="space-y-6">
      <UiInput label="New Password" type="password" placeholder="••••••••" required />
      <UiInput label="Confirm Password" type="password" placeholder="••••••••" required />
      <UiButton type="submit" class="w-full">Reset Password</UiButton>
    </form>
  </div>
</template>
