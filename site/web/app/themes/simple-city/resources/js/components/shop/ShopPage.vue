<template>
  <v-app>
    <v-main>
      <div class="shop-page">
        <!-- Filters -->
        <FilterBar />

    <!-- Error State -->
    <div v-if="shopStore.error" class="error">
      <p>{{ shopStore.error }}</p>
    </div>

    <!-- Products Grid -->
    <div
      v-else-if="shopStore.loading || shopStore.hasProducts"
      class="products-grid"
      :class="[`grid-cols-${shopStore.gridColumns}`, { 'is-reloading': shopStore.loading && shopStore.hasProducts }]"
    >
      <!-- Skeleton on initial load (no products in store yet) -->
      <template v-if="shopStore.loading && !shopStore.hasProducts">
        <ProductCardSkeleton
          v-for="n in shopStore.filters.perPage"
          :key="`sk-${n}`"
        />
      </template>

      <!-- Real products (shown during filter-change loading too, dimmed) -->
      <template v-else>
        <ProductCard
          v-for="product in shopStore.products"
          :key="product.id"
          :product="product"
        />
      </template>
    </div>

    <!-- No Products -->
    <div v-else class="no-products">
      <p>Δεν βρέθηκαν προϊόντα.</p>
    </div>

    <!-- Load More Button -->
    <div v-if="shopStore.hasMore && shopStore.hasProducts" class="load-more-container">
      <button
        @click="shopStore.loadMoreProducts()"
        :disabled="shopStore.loadingMore"
        class="load-more-button"
      >
        {{ shopStore.loadingMore ? 'Φορτώνει...' : `Δείτε ακόμα ${shopStore.pagination.total - shopStore.products.length} προϊόντα` }}
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
import ProductCardSkeleton from './ProductCardSkeleton.vue';

const shopStore = useShopStore();

onMounted(async () => {
  shopStore.initFromURL();
  shopStore.initGridColumns();

  const f = shopStore.filters;
  const hasActiveFilters =
    f.category ||
    f.tags.length > 0 ||
    f.colors.length > 0 ||
    f.materials.length > 0 ||
    f.height || f.width || f.depth;

  if (hasActiveFilters) {
    await Promise.all([
      shopStore.fetchCategories(),
      shopStore.fetchTags(),
      shopStore.fetchColors(),
      shopStore.fetchMaterials(),
      shopStore.fetchHeights(),
      shopStore.fetchWidths(),
      shopStore.fetchDepths(),
      shopStore.fetchPriceRange(),
    ]);
  } else {
    await shopStore.fetchInit();
  }

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

    // Scroll to filter-bar sticky point on click
    searchInput.addEventListener('click', () => {
      const filterBar = document.querySelector('.filter-bar');
      if (!filterBar) return;
      const headerHeight = parseFloat(
        getComputedStyle(document.documentElement).getPropertyValue('--header-height')
      ) || 80;
      const targetY = filterBar.getBoundingClientRect().top + window.scrollY - headerHeight;
      window.scrollTo({ top: targetY, behavior: 'smooth' });
    });

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

.error,
.no-products {
  text-align: center;
  padding: 3rem;
  font-size: 1.2rem;
}

.error {
  color: #dc3545;
}

.products-grid.is-reloading {
  opacity: 0.45;
  pointer-events: none;
  transition: opacity 0.15s ease;
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
