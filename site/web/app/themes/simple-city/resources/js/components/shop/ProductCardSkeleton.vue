<template>
  <div class="product-card-skeleton">
    <div class="skeleton-image"></div>
    <div class="skeleton-info">
      <div class="skeleton-line skeleton-title"></div>
      <div class="skeleton-line skeleton-title skeleton-title--short"></div>
      <div class="skeleton-line skeleton-price"></div>
    </div>
  </div>
</template>

<style scoped>
.product-card-skeleton {
  border-radius: 8px;
  overflow: hidden;
}

.skeleton-image {
  width: 100%;
  aspect-ratio: 4 / 3;
}

.skeleton-info {
  padding: 1rem;
}

.skeleton-line {
  border-radius: 4px;
  margin-bottom: 0.5rem;
}

.skeleton-title {
  height: 14px;
  width: 85%;
}

.skeleton-title--short {
  width: 55%;
}

.skeleton-price {
  height: 14px;
  width: 35%;
  margin-top: 0.75rem;
  margin-bottom: 0;
}

.skeleton-image,
.skeleton-line {
  background: linear-gradient(90deg, #efefef 25%, #e0e0e0 50%, #efefef 75%);
  background-size: 200% 100%;
  animation: shimmer 1.4s infinite linear;
}

@keyframes shimmer {
  0%   { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}
</style>
