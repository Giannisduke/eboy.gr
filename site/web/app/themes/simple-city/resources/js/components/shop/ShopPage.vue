<template>
  <v-app>
    <v-main>
      <div class="shop-page">
        <!-- Filters -->
        <FilterBar />

    <!-- Loading State -->
    <div v-if="shopStore.loading" class="loading">
      <p>Loading products...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="shopStore.error" class="error">
      <p>{{ shopStore.error }}</p>
    </div>

    <!-- Products Grid -->
    <div
      v-else-if="shopStore.hasProducts"
      class="products-grid"
      :class="`grid-cols-${shopStore.gridColumns}`"
    >
      <ProductCard
        v-for="product in shopStore.products"
        :key="product.id"
        :product="product"
      />
    </div>

    <!-- No Products -->
    <div v-else class="no-products">
      <p>No products found.</p>
    </div>

    <!-- Load More Button -->
    <div v-if="shopStore.hasMore && shopStore.hasProducts" class="load-more-container">
      <button
        @click="shopStore.loadMoreProducts()"
        :disabled="shopStore.loadingMore"
        class="load-more-button"
      >
        {{ shopStore.loadingMore ? 'Φορτώνει...' : 'Δείτε Περισσότερα' }}
      </button>
    </div>
      </div>
    </v-main>
  </v-app>
</template>

<script setup>
import { onMounted, nextTick, watch } from 'vue';
import { useShopStore } from '../../stores/shop';
import FilterBar from './FilterBar.vue';
import ProductCard from './ProductCard.vue';

const shopStore = useShopStore();

onMounted(async () => {
  // Initialize filters from URL parameters
  shopStore.initFromURL();

  // Initialize grid columns from localStorage
  shopStore.initGridColumns();

  // Fetch categories, tags, colors, materials, heights, price range, and products
  await shopStore.fetchCategories();
  await shopStore.fetchTags();
  await shopStore.fetchColors();
  await shopStore.fetchMaterials();
  await shopStore.fetchHeights();
  await shopStore.fetchWidths();
  await shopStore.fetchDepths();
  await shopStore.fetchPriceRange();
  await shopStore.fetchProducts();

  await nextTick();

  // Connect grid view buttons to Vue store
  setupGridButtons();

  // Connect header search to Vue store
  setupHeaderSearch();
});

function setupHeaderSearch() {
  const searchInput = document.getElementById('shop-search-input');

  if (searchInput) {
    // Set initial value from store
    searchInput.value = shopStore.filters.search;

    let searchTimeout = null;

    // Listen for input changes
    searchInput.addEventListener('input', (e) => {
      clearTimeout(searchTimeout);
      searchTimeout = setTimeout(() => {
        shopStore.setSearch(e.target.value);
      }, 500); // Debounce search
    });

    // Watch store changes to keep input in sync
    watch(() => shopStore.filters.search, (newVal) => {
      if (searchInput.value !== newVal) {
        searchInput.value = newVal;
      }
    });
  }
}

function setupGridButtons() {
  const grid2 = document.getElementById('grid_2');
  const grid4 = document.getElementById('grid_4');
  const grid6 = document.getElementById('grid_6');

  if (grid2) {
    grid2.addEventListener('click', () => {
      shopStore.setGridColumns(2);
      updateButtonStates();
    });
  }

  if (grid4) {
    grid4.addEventListener('click', () => {
      shopStore.setGridColumns(4);
      updateButtonStates();
    });
  }

  if (grid6) {
    grid6.addEventListener('click', () => {
      shopStore.setGridColumns(6);
      updateButtonStates();
    });
  }

  // Set initial button states
  updateButtonStates();
}

function updateButtonStates() {
  const grid2 = document.getElementById('grid_2');
  const grid4 = document.getElementById('grid_4');
  const grid6 = document.getElementById('grid_6');

  [grid2, grid4, grid6].forEach(btn => {
    if (btn) btn.classList.remove('selected');
  });

  if (shopStore.gridColumns === 2 && grid2) grid2.classList.add('selected');
  if (shopStore.gridColumns === 4 && grid4) grid4.classList.add('selected');
  if (shopStore.gridColumns === 6 && grid6) grid6.classList.add('selected');
}
</script>

<style scoped lang="scss">
@import 'bootstrap/scss/functions';
@import '../../../css/custom/shared-variables';
@import 'bootstrap/scss/variables';
@import 'bootstrap/scss/maps';
@import 'bootstrap/scss/mixins';
@import "bootstrap/scss/containers";
@import "bootstrap/scss/grid";

.shop-page {
  @include make-container();
}

.products-grid {
  display: grid;
  gap: 2rem;
  margin: 2rem 0;
}

/* Grid column variations */
.products-grid.grid-cols-2 {
  grid-template-columns: repeat(2, 1fr);
}

.products-grid.grid-cols-4 {
  grid-template-columns: repeat(4, 1fr);
}

.products-grid.grid-cols-6 {
  grid-template-columns: repeat(6, 1fr);
}

.loading,
.error,
.no-products {
  text-align: center;
  padding: 3rem;
  font-size: 1.2rem;
}

.error {
  color: #dc3545;
}

.load-more-container {
  display: flex;
  justify-content: center;
  padding: 3rem 0;
}

.load-more-button {
  padding: 1rem 3rem;
  font-size: 1rem;
  font-weight: 600;
  color: #fff;
  background-color: #333;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  transition: all 0.3s ease;
}

.load-more-button:hover:not(:disabled) {
  background-color: #555;
  transform: translateY(-2px);
}

.load-more-button:disabled {
  background-color: #999;
  cursor: not-allowed;
  opacity: 0.6;
}

@media (max-width: 1200px) {
  .products-grid.grid-cols-6 {
    grid-template-columns: repeat(4, 1fr);
  }
}

@media (max-width: 768px) {
  .products-grid.grid-cols-2 {
    grid-template-columns: repeat(2, 1fr);
  }

  .products-grid.grid-cols-4 {
    grid-template-columns: repeat(2, 1fr);
  }

  .products-grid.grid-cols-6 {
    grid-template-columns: repeat(3, 1fr);
  }
}

@media (max-width: 480px) {
  .products-grid {
    grid-template-columns: repeat(2, 1fr) !important;
    gap: 1rem;
  }
}
</style>
