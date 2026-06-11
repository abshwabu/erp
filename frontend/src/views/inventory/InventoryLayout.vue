<template>
  <div>
    <h3 class="text-2xl font-bold mb-6">Inventory Management</h3>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
      <div class="bg-white p-6 rounded-lg border border-slate-200 shadow-sm">
        <p class="text-sm text-slate-500 uppercase font-bold tracking-wider">Total Products</p>
        <p class="text-3xl font-bold mt-2">1,234</p>
      </div>
      <div class="bg-white p-6 rounded-lg border border-slate-200 shadow-sm">
        <p class="text-sm text-slate-500 uppercase font-bold tracking-wider">Low Stock Items</p>
        <p class="text-3xl font-bold mt-2 text-red-600">12</p>
      </div>
      <div class="bg-white p-6 rounded-lg border border-slate-200 shadow-sm">
        <p class="text-sm text-slate-500 uppercase font-bold tracking-wider">Categories</p>
        <p class="text-3xl font-bold mt-2">45</p>
      </div>
    </div>
    <router-view />
  </div>
</template>
