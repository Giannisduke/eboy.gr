<template>
  <div class="pagination">
    <button
      class="pagination-btn"
      :disabled="shopStore.isFirstPage"
      @click="shopStore.prevPage()"
    >
      Previous
    </button>

    <div class="pagination-pages">
      <button
        v-for="page in visiblePages"
        :key="page"
        class="pagination-page"
        :class="{ active: page === shopStore.pagination.currentPage }"
        @click="shopStore.setPage(page)"
      >
        {{ page }}
      </button>
    </div>

    <button
      class="pagination-btn"
      :disabled="shopStore.isLastPage"
      @click="shopStore.nextPage()"
    >
      Next
    </button>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useShopStore } from '../../stores/shop';

const shopStore = useShopStore();

const visiblePages = computed(() => {
  const current = shopStore.pagination.currentPage;
  const total = shopStore.pagination.totalPages;
  const pages = [];

  // Show max 5 page numbers
  let start = Math.max(1, current - 2);
  let end = Math.min(total, current + 2);

  // Adjust if at the beginning or end
  if (current <= 3) {
    end = Math.min(5, total);
  }
  if (current >= total - 2) {
    start = Math.max(1, total - 4);
  }

  for (let i = start; i <= end; i++) {
    pages.push(i);
  }

  return pages;
});
</script>

<style scoped>
.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 0.5rem;
  margin: 3rem 0;
}

.pagination-btn {
  padding: 0.75rem 1.5rem;
  background: #007bff;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 1rem;
  transition: background 0.2s;
}

.pagination-btn:hover:not(:disabled) {
  background: #0056b3;
}

.pagination-btn:disabled {
  background: #ccc;
  cursor: not-allowed;
}

.pagination-pages {
  display: flex;
  gap: 0.25rem;
}

.pagination-page {
  width: 40px;
  height: 40px;
  border: 1px solid #ddd;
  background: white;
  border-radius: 4px;
  cursor: pointer;
  font-size: 1rem;
  transition: all 0.2s;
}

.pagination-page:hover {
  border-color: #007bff;
  color: #007bff;
}

.pagination-page.active {
  background: #007bff;
  color: white;
  border-color: #007bff;
}

@media (max-width: 768px) {
  .pagination {
    flex-wrap: wrap;
  }

  .pagination-btn {
    padding: 0.5rem 1rem;
    font-size: 0.9rem;
  }

  .pagination-page {
    width: 35px;
    height: 35px;
    font-size: 0.9rem;
  }
}
</style>
