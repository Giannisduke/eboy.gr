<template>
  <div class="product-card">
    <a :href="product.permalink" class="product-link">
      <!-- Sale Badge -->
      <span v-if="product.on_sale" class="badge sale-badge">Sale</span>

      <!-- Product Image -->
      <div class="product-image">
        <img
          :src="product.images[0]?.src || placeholderImage"
          :alt="product.name"
          loading="lazy"
        />
      </div>

      <!-- Product Info -->
      <div class="product-info">
        <h3 class="product-title">{{ product.name }}</h3>

        <!-- Price -->
        <div class="product-price">
          <span v-if="product.on_sale" class="regular-price">
            <del v-html="product.regular_price_html"></del>
          </span>
          <span class="sale-price" v-html="product.price_html"></span>
        </div>
      </div>
    </a>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  product: {
    type: Object,
    required: true
  }
});

const placeholderImage = computed(() => {
  return '/app/themes/simple-city/resources/images/placeholder.png';
});
</script>

<style scoped>
.product-card {
  background: #fff;
  border-radius: 8px;
  overflow: hidden;
  transition: transform 0.3s ease, box-shadow 0.3s ease;
  position: relative;
}

.product-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
}

.product-link {
  text-decoration: none;
  color: inherit;
  display: block;
}

.sale-badge {
  position: absolute;
  top: 10px;
  right: 10px;
  background: #dc3545;
  color: white;
  padding: 4px 12px;
  border-radius: 20px;
  font-size: 0.85rem;
  font-weight: 600;
  z-index: 1;
}

.product-image {
  width: 100%;
  aspect-ratio: 1;
  overflow: hidden;
  background: #f8f9fa;
}

.product-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.3s ease;
}

.product-card:hover .product-image img {
  transform: scale(1.05);
}

.product-info {
  padding: 1rem;
}

.product-title {
  font-size: 1rem;
  font-weight: 600;
  margin: 0 0 0.5rem;
  color: #333;
  line-height: 1.4;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.product-price {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.regular-price {
  color: #999;
  font-size: 0.9rem;
}

.sale-price {
  font-size: 1.1rem;
  font-weight: 700;
  color: #dc3545;
}
</style>
