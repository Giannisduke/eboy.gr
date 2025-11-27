<template>
  <div class="filter-bar">
    <!-- Search -->
    <div class="filter-group search-group">
      <input
        v-model="searchQuery"
        type="text"
        placeholder="Search products..."
        class="search-input"
        @input="onSearchInput"
      />
    </div>

    <!-- Sale Filter -->
    <div class="filter-group">
      <label class="checkbox-label">
        <input
          type="checkbox"
          v-model="showOnSale"
          @change="onSaleChange"
        />
        <span>On Sale</span>
      </label>
    </div>

    <!-- Category Filter -->
    <div class="filter-group category-group">
      <select
        v-model="selectedCategory"
        class="category-select"
        @change="onCategoryChange"
      >
        <option :value="null">All Categories</option>
        <option
          v-for="category in shopStore.categories"
          :key="category.id"
          :value="category.id"
        >
          {{ category.name }}
        </option>
      </select>
    </div>

    <!-- Sort -->
    <div class="filter-group">
      <select
        v-model="sortOption"
        class="sort-select"
        @change="onSortChange"
      >
        <option value="menu_order-asc">Default sorting</option>
        <option value="popularity-desc">Popularity</option>
        <option value="date-desc">Latest</option>
        <option value="price-asc">Price: Low to High</option>
        <option value="price-desc">Price: High to Low</option>
      </select>
    </div>

    <!-- Results Count -->
    <div class="results-count">
      Showing {{ shopStore.products.length }} of {{ shopStore.pagination.total }} products
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useShopStore } from '../../stores/shop';

const shopStore = useShopStore();

const searchQuery = ref('');
const selectedCategory = ref(null);
const showOnSale = ref(false);
const sortOption = ref('menu_order-asc');

let searchTimeout = null;

const onSearchInput = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    shopStore.setSearch(searchQuery.value);
  }, 500); // Debounce search
};

const onCategoryChange = () => {
  shopStore.setCategory(selectedCategory.value);
};

const onSaleChange = () => {
  shopStore.setOnSale(showOnSale.value);
};

const onSortChange = () => {
  const [orderby, order] = sortOption.value.split('-');
  shopStore.setSort(orderby, order);
};
</script>

<style scoped>
.filter-bar {
  background: #f8f9fa;
  padding: 1.5rem;
  border-radius: 8px;
  margin-bottom: 2rem;
  display: flex;
  flex-wrap: wrap;
  gap: 1rem;
  align-items: center;
}

.filter-group {
  flex: 1;
  min-width: 200px;
}

.search-group {
  flex: 2;
}

.search-input,
.category-select,
.sort-select {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 1rem;
}

.search-input:focus,
.category-select:focus,
.sort-select:focus {
  outline: none;
  border-color: #007bff;
}

.checkbox-label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
  font-size: 1rem;
}

.checkbox-label input[type="checkbox"] {
  width: 18px;
  height: 18px;
  cursor: pointer;
}

.results-count {
  flex: 1 1 100%;
  text-align: center;
  color: #666;
  font-size: 0.95rem;
  padding-top: 0.5rem;
  border-top: 1px solid #ddd;
  margin-top: 0.5rem;
}

@media (max-width: 768px) {
  .filter-bar {
    flex-direction: column;
  }

  .filter-group,
  .search-group {
    width: 100%;
    min-width: auto;
  }
}
</style>
