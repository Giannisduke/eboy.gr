<template>
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
    <div v-else-if="shopStore.hasProducts" class="products-grid">
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

    <!-- Pagination -->
    <Pagination v-if="shopStore.pagination.totalPages > 1" />
  </div>
</template>

<script setup>
import { onMounted } from 'vue';
import { useShopStore } from '../../stores/shop';
import FilterBar from './FilterBar.vue';
import ProductCard from './ProductCard.vue';
import Pagination from './Pagination.vue';

const shopStore = useShopStore();

onMounted(async () => {
  await shopStore.fetchCategories();
  await shopStore.fetchProducts();
});
</script>

<style scoped>
.shop-page {
  width: 100%;
}

.products-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
  gap: 2rem;
  margin: 2rem 0;
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

@media (max-width: 768px) {
  .products-grid {
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 1rem;
  }
}
</style>
