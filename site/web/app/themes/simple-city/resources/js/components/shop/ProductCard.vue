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

        <!-- Price -->
        <div class="product-price">
          <span class="price-display" v-html="product.price_html"></span>
        </div>

      <!-- Product Info -->
      <div class="product-info">
        <h3 class="product-title">{{ product.name }}</h3>


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

<style lang="scss" scoped>
.product-card {
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
  display: none;
}

.product-image {
  width: 100%;
  aspect-ratio: 4 / 3;
  overflow: hidden;
  background: #f8f9fa;
  display: flex;
  align-items: center;
  justify-content: center;
}

.product-image img {
  width: 100%;
  height: 100%;
  object-fit: contain;
  transition: transform 0.3s ease;
  padding: 0.5rem;
}

.product-card:hover .product-image img {
  transform: scale(1.05);
}

.product-info {
  padding: 0rem 0rem 0rem .5rem;
}

.product-title {
  font-size: 1rem;
  margin: 0 0 0.5rem;
  color: $primary;
  line-height: 1.4;
  font-family: 'PFBagueSansPro-Medium';
}

.product-price {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 0.2rem;
  position: relative;
  bottom: .5rem;
}

.price-display {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  background: $primary;
  font-family: 'PFBagueSansPro-Medium';
  color: $white;



  :deep(ins) {
    display: block;
    order: -1;
    text-decoration: none;
    background: $secondary;
    color: $dark;
    font-size: 0.8rem;
    padding: 0 .25rem;
    position: absolute;
    bottom: 1.21rem;
  }

  :deep(del) {
    display: block;
    font-family: 'PFBagueSansPro-Medium';
    color: $white;
    font-size: 0.8rem;
    padding: 0 .25rem;
  }

  :deep(bdi) {
    font-size: 0.8rem;
    padding: 0 .25rem;
  }

}
</style>
