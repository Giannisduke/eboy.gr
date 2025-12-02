<template>
  <div class="filter-bar">
    <!-- Category Menu with Icons -->
    <div class="category-menu">
      <button
        class="category-btn"
        :class="{ active: showOnSale }"
        @click="toggleOnSale"
      >
        <img
          :src="salesIcon"
          alt="Προσφορές"
          class="category-icon"
        />
        <span class="category-name">Προσφορές</span>
      </button>
      <button
        v-for="category in shopStore.categories"
        :key="category.id"
        class="category-btn"
        :class="{ active: selectedCategory === category.id }"
        @click="selectCategory(category.id)"
      >
        <img
          v-if="getCategoryIcon(category.slug)"
          :src="getCategoryIcon(category.slug)"
          :alt="category.name"
          class="category-icon"
        />
        <span class="category-name">{{ category.name }}</span>
      </button>
    </div>

    <!-- Filters Container (Two Columns) -->
    <div class="filters-container">
      <!-- Tag Cloud (Left Column) -->
      <div v-if="shopStore.tags.length > 0" class="tag-cloud">
        <button
          v-for="tag in shopStore.tags"
          :key="tag.id"
          class="tag-btn"
          :class="{
            active: shopStore.filters.tags.includes(tag.id),
            disabled: tag.available === false && !shopStore.filters.tags.includes(tag.id)
          }"
          :style="{ fontSize: getTagSize(tag.count) }"
          :disabled="tag.available === false && !shopStore.filters.tags.includes(tag.id)"
          @click="toggleTag(tag.id)"
        >
          {{ tag.name }} <span class="tag-count">({{ tag.count }})</span>
        </button>
      </div>

      <!-- Υλικά Column -->
      <div v-if="shopStore.materials.length > 0" class="material">

        <button
          v-for="material in shopStore.materials"
          :key="material.id"
          class="material-btn"
          :class="{
            active: shopStore.filters.materials.includes(material.id),
            disabled: material.available === false && !shopStore.filters.materials.includes(material.id)
          }"
          :style="{ fontSize: getMaterialSize(material.count) }"
          :disabled="material.available === false && !shopStore.filters.materials.includes(material.id)"
          @click="toggleMaterial(material.id)"
        >
          {{ material.name }} <span class="material-count">({{ material.count }})</span>
        </button>
      </div>
        <!-- Price Range Slider (Right Side) -->
        <div class="extra-filters">
          <div class="price-filter">
        
            <div v-if="isPriceRangeRestricted" class="price-range-info">
              Διαθέσιμο: {{ shopStore.priceRange.filteredMin.toFixed(2) }}€ - {{ shopStore.priceRange.filteredMax.toFixed(2) }}€
            </div>

            <div class="price-slider">
              <v-range-slider
                v-model="priceRange"
                :min="shopStore.priceRange.filteredMin"
                :max="shopStore.priceRange.filteredMax"
                :step="1"
                hide-details
                color="primary"
                track-color="#ddd"
                @update:model-value="onPriceRangeChange"
                class="mt-4"
                thumb-label="always"
              ></v-range-slider>
            </div>
          </div>
             
        
        <!-- Color Filters (Left Side) -->
        <div v-if="shopStore.colors.length > 0" class="color-filters">

          <div class="color-swatches">
            <button
              v-for="color in shopStore.colors"
              :key="color.id"
              class="color-swatch"
              :class="[
                color.slug,
                {
                  active: shopStore.filters.colors.includes(color.id),
                  disabled: color.available === false && !shopStore.filters.colors.includes(color.id)
                }
              ]"
              :style="{ backgroundColor: color.slug }"
              :title="color.name"
              :disabled="color.available === false && !shopStore.filters.colors.includes(color.id)"
              @click="toggleColor(color.id)"
            >
            </button>
          
       
        

        <!-- Height Filter -->
        <div v-if="shopStore.heights.length > 0" class="height-filter">
          <select
            v-model="selectedHeight"
            @change="onHeightChange"
            class="height-select"
          >
            <option :value="null">Όλα τα ύψη</option>
            <option
              v-for="height in shopStore.heights"
              :key="height.slug"
              :value="height.slug"
              :disabled="height.available === false"
            >
              {{ height.name }} ({{ height.count }})
            </option>
          </select>
        </div>

        <!-- Width Filter -->
        <div v-if="shopStore.widths.length > 0" class="width-filter">
          <select
            v-model="selectedWidth"
            @change="onWidthChange"
            class="width-select"
          >
            <option :value="null">Όλα τα πλάτη</option>
            <option
              v-for="width in shopStore.widths"
              :key="width.slug"
              :value="width.slug"
              :disabled="width.available === false"
            >
              {{ width.name }} ({{ width.count }})
            </option>
          </select>
        </div>

        <!-- Depth Filter -->
        <div v-if="shopStore.depths.length > 0" class="depth-filter">
          <select
            v-model="selectedDepth"
            @change="onDepthChange"
            class="depth-select"
          >
            <option :value="null">Όλα τα μήκη</option>
            <option
              v-for="depth in shopStore.depths"
              :key="depth.slug"
              :value="depth.slug"
              :disabled="depth.available === false"
            >
              {{ depth.name }} ({{ depth.count }})
            </option>
          </select>
        </div>
        </div>
      </div>
        </div>
              <!-- Additional Filters (Right Column) -->

    </div>

    <!-- Results Count -->
    <div class="results-count">
      Εμφάνιση {{ shopStore.products.length }} από {{ shopStore.pagination.total }} προϊόντα
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onMounted, computed } from 'vue';
import { useShopStore } from '../../stores/shop';

// Import SVG icons
import bathroomIcon from '../../../images/bathroom.svg';
import bedroomIcon from '../../../images/bedroom.svg';
import gardenIcon from '../../../images/garden.svg';
import kitchenIcon from '../../../images/kitchen.svg';
import officeIcon from '../../../images/office.svg';
import salesIcon from '../../../images/sales.svg';
import saloniIcon from '../../../images/saloni.svg';

const shopStore = useShopStore();

const selectedCategory = ref(null);
const showOnSale = ref(false);
const selectedTags = ref([]);
const selectedHeight = ref(null);
const selectedWidth = ref(null);
const selectedDepth = ref(null);
const localMinPrice = ref(0);
const localMaxPrice = ref(1000);
const priceRange = ref([0, 1000]);
let priceUpdateTimeout = null;

// Sync local state with store on mount (for URL initialization)
onMounted(() => {
  selectedCategory.value = shopStore.filters.category;
  showOnSale.value = shopStore.filters.onSale;
  selectedTags.value = [...shopStore.filters.tags];
  selectedHeight.value = shopStore.filters.height;
  selectedWidth.value = shopStore.filters.width;
  selectedDepth.value = shopStore.filters.depth;
  // Use filtered range for initialization
  localMinPrice.value = shopStore.filters.minPrice || shopStore.priceRange.filteredMin;
  localMaxPrice.value = shopStore.filters.maxPrice || shopStore.priceRange.filteredMax;
  priceRange.value = [localMinPrice.value, localMaxPrice.value];
});

// Watch store changes to keep local state in sync
watch(() => shopStore.filters.category, (newVal) => {
  selectedCategory.value = newVal;
});

watch(() => shopStore.filters.onSale, (newVal) => {
  showOnSale.value = newVal;
});

watch(() => shopStore.filters.tags, (newVal) => {
  selectedTags.value = [...newVal];
});

watch(() => shopStore.filters.height, (newVal) => {
  selectedHeight.value = newVal;
});

watch(() => shopStore.filters.width, (newVal) => {
  selectedWidth.value = newVal;
});

watch(() => shopStore.filters.depth, (newVal) => {
  selectedDepth.value = newVal;
});

// Watch price range changes (both overall and filtered)
watch(() => [shopStore.priceRange.min, shopStore.priceRange.max, shopStore.priceRange.filteredMin, shopStore.priceRange.filteredMax], ([min, max, filteredMin, filteredMax]) => {
  // Use filtered range as the slider bounds
  // Constrain current values to the new filtered range
  localMinPrice.value = Math.max(shopStore.filters.minPrice || filteredMin, filteredMin);
  localMaxPrice.value = Math.min(shopStore.filters.maxPrice || filteredMax, filteredMax);
  priceRange.value = [localMinPrice.value, localMaxPrice.value];
});

// Computed property to check if current range is outside filtered range
const isPriceRangeRestricted = computed(() => {
  return shopStore.priceRange.filteredMin > shopStore.priceRange.min ||
         shopStore.priceRange.filteredMax < shopStore.priceRange.max;
});

// Category icon mapping
const categoryIcons = {
  'bathroom': bathroomIcon,
  'mpanio': bathroomIcon,
  'bedroom': bedroomIcon,
  'ypnodomatio': bedroomIcon,
  'garden': gardenIcon,
  'kipos': gardenIcon,
  'kitchen': kitchenIcon,
  'kouzina': kitchenIcon,
  'office': officeIcon,
  'grafeio': officeIcon,
  'sales': salesIcon,
  'prosfores': salesIcon,
  'saloni': saloniIcon,
  'salon': saloniIcon,
};

const getCategoryIcon = (slug) => {
  return categoryIcons[slug] || null;
};

const selectCategory = (categoryId) => {
  // Toggle category: if already selected, deselect it
  if (selectedCategory.value === categoryId) {
    selectedCategory.value = null;
    shopStore.setCategory(null);
  } else {
    selectedCategory.value = categoryId;
    shopStore.setCategory(categoryId);
  }
};

const toggleOnSale = () => {
  showOnSale.value = !showOnSale.value;
  shopStore.setOnSale(showOnSale.value);
};

const toggleTag = (tagId) => {
  shopStore.toggleTag(tagId);
};

const toggleColor = (colorId) => {
  shopStore.toggleColor(colorId);
};

const toggleMaterial = (materialId) => {
  shopStore.toggleMaterial(materialId);
};

const onHeightChange = () => {
  shopStore.setHeight(selectedHeight.value);
};

const onWidthChange = () => {
  shopStore.setWidth(selectedWidth.value);
};

const onDepthChange = () => {
  shopStore.setDepth(selectedDepth.value);
};

const onPriceRangeChange = (value) => {
  localMinPrice.value = value[0];
  localMaxPrice.value = value[1];
  debouncedPriceUpdate();
};

const debouncedPriceUpdate = () => {
  clearTimeout(priceUpdateTimeout);
  priceUpdateTimeout = setTimeout(() => {
    updatePriceRange();
  }, 500);
};

const updatePriceRange = () => {
  shopStore.setPriceRange(localMinPrice.value, localMaxPrice.value);
};

const getTagSize = (count) => {
  // Calculate font size based on product count (tag cloud effect)
  const minSize = 0.65;
  const maxSize = 1;
  const minCount = Math.min(...shopStore.tags.map(t => t.count));
  const maxCount = Math.max(...shopStore.tags.map(t => t.count));

  if (maxCount === minCount) return `${minSize}rem`;

  const size = minSize + ((count - minCount) / (maxCount - minCount)) * (maxSize - minSize);
  return `${size.toFixed(2)}rem`;
};

const getMaterialSize = (count) => {
  // Calculate font size based on product count (material cloud effect)
  const minSize = 0.65;
  const maxSize = 1;
  const minCount = Math.min(...shopStore.materials.map(m => m.count));
  const maxCount = Math.max(...shopStore.materials.map(m => m.count));

  if (maxCount === minCount) return `${minSize}rem`;

  const size = minSize + ((count - minCount) / (maxCount - minCount)) * (maxSize - minSize);
  return `${size.toFixed(2)}rem`;
};
</script>

<style scoped lang="scss">
@import 'bootstrap/scss/functions';
@import '../../../css/custom/shared-variables';
@import 'bootstrap/scss/variables';
@import 'bootstrap/scss/maps';
@import 'bootstrap/scss/mixins';
@import 'bootstrap/scss/forms';
@import "bootstrap/scss/buttons";
@import "bootstrap/scss/containers";
@import "bootstrap/scss/grid";
@import "bootstrap/scss/utilities";
@import "bootstrap/scss/utilities/api";

.filter-bar {
  @include make-row();
}

/* Category Menu */
.category-menu {
      @include make-col-ready();
      @include media-breakpoint-up(lg) {
      @include make-col(12);
    }
    @extend .py-5;
 // overflow-x: auto;
  @extend .d-flex;
  @extend .justify-content-between;

}

.category-btn {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
  padding: 1rem;
  background: transparent;
  border: none;
  cursor: pointer;
  transition: all 0.3s ease;
  border-radius: 8px;
  min-width: 100px;
  position: relative;
}

.category-btn:hover {
  background: #f5f5f5;
  transform: translateY(-2px);
}

.category-btn.active {
  background: #f0f0f0;
}

.category-btn.active::after {
  content: '';
  position: absolute;
  bottom: -1rem;
  left: 50%;
  transform: translateX(-50%);
  width: 60%;
  height: 3px;
  background: $secondary;
}

.category-icon {
  width: 48px;
  height: 48px;
  object-fit: contain;
}

.category-name {
  font-size: 0.9rem;
  font-weight: 500;
  text-align: center;
  white-space: nowrap;
}

/* Filters Container (Two Columns) */
.filters-container {
  @include make-row();

/* Tag Cloud (Left Column - 50%) */
.tag-cloud {
      @include make-col-ready();
      @include media-breakpoint-up(lg) {
      @include make-col(5);
    }
    @extend .pt-4;
}

/* Material Column */
.material {
      @include make-col-ready();
      @include media-breakpoint-up(lg) {
      @include make-col(3);
    }
      @extend .pt-4;
      @extend .pe-0;

}

/* Additional Filters (Right Column - 50%) */
.additional-filters {
      @include make-col-ready();
      @include media-breakpoint-up(lg) {
      @include make-col(12);
    }
}

/* Color Filters (Left Side of Additional Filters) */
.color-filters {
                      @include make-col-ready();
    
                    @include media-breakpoint-up(md) {
                        @include make-col(12);
                    }
}

.height-filter {
    @include make-col-ready();

    @include media-breakpoint-up(sm) {
    @include make-col(4);
    }
    @include media-breakpoint-up(lg) {
    @include make-col(3);
    }
}

.width-filter {
    @include make-col-ready();

    @include media-breakpoint-up(sm) {
    @include make-col(4);
    }
    @include media-breakpoint-up(lg) {
    @include make-col(3);
    }
}

.depth-filter {
    @include make-col-ready();

    @include media-breakpoint-up(sm) {
    @include make-col(4);
    }
    @include media-breakpoint-up(lg) {
    @include make-col(3);
    }
}

.height-select,
.width-select,
.depth-select {
  padding: 0.5rem;
  border: 2px solid #ddd;
  border-radius: 4px;
  font-size: 0.9rem;
  background: white;
  cursor: pointer;
  transition: border-color 0.3s ease;
}

.height-select:hover,
.width-select:hover,
.depth-select:hover {
  border-color: #999;
}

.height-select:focus,
.width-select:focus,
.depth-select:focus {
  outline: none;
  border-color: #000;
}

.height-select option:disabled,
.width-select option:disabled,
.depth-select option:disabled {
  color: #ccc;
  font-style: italic;
}

.extra-filters {
      @include make-col(4);
      @include media-breakpoint-up(lg) {
      @include make-col(4);
    }
    @extend .ps-3;
}

/* Price Filter */
.price-filter {
    @include make-col-ready();

    @include media-breakpoint-up(md) {
    @include make-col(12);
    }
    @extend .pb-4;
}

.price-range-info {
  font-size: 0.85rem;
  color: #666;
  background: #fff3cd;
  padding: 0.5rem;
  border-radius: 4px;
  text-align: center;
  border: 1px solid #ffd700;
}

.price-inputs {
  display: flex;
  gap: 1rem;
  align-items: center;
}

.price-input-group {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.price-input-group label {
  font-size: 0.9rem;
  color: #666;
  min-width: 40px;
}

.price-input {
  width: 80px;
  padding: 0.5rem;
  border: 2px solid #ddd;
  border-radius: 4px;
  font-size: 0.9rem;
  text-align: center;
}

.price-input:focus {
  outline: none;
  border-color: #000;
}

.price-input-group span {
  font-size: 0.9rem;
  color: #666;
}

/* Price Slider */
.price-slider {

}

.filter-title {
  font-size: 1rem;
  font-weight: 600;
  margin: 0;
  color: #333;
}

.color-swatches {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
  align-items: flex-start;
}

.color-swatch {
  position: relative;
  width: 25px;
  height: 25px;
  border-radius: 50%;
  border: 2px solid #ddd;
  cursor: pointer;
  transition: all 0.3s ease;
  overflow: hidden;
}

.color-swatch:hover {
  transform: scale(1.1);
  border-color: #999;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
}

.color-swatch.active {
  border-color: #000;
  border-width: 4px;
  box-shadow: 0 0 0 2px #ffd700;
}

.color-swatch.disabled {
  opacity: 0.3;
  cursor: not-allowed;
  filter: grayscale(50%);
}

.color-swatch.disabled:hover {
  transform: none;
  border-color: #ddd;
  box-shadow: none;
}

.color-name {
  position: absolute;
  bottom: -25px;
  left: 50%;
  transform: translateX(-50%);
  font-size: 0.75rem;
  color: #666;
  white-space: nowrap;
  pointer-events: none;
  opacity: 0;
  transition: opacity 0.3s ease;
}

.color-swatch:hover .color-name {
  opacity: 1;
}

.tag-btn {
  @extend .btn;
  @extend .btn-primary;
  @extend .m-1;
}

.tag-btn:hover {
 // border-color: #999;
  background: $secondary;
  transform: translateY(-2px);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.tag-btn.active {
  background: #ffd700;
  border-color: #ffd700;
  color: #000;
  font-weight: 600;
}

.tag-btn.active:hover {
  background: #ffed4e;
  border-color: #ffed4e;
}

.tag-btn.disabled {
  background: #f5f5f5;
  color: #ccc;
  border-color: #e5e5e5;
  cursor: not-allowed;
  opacity: 0.5;
}

.tag-btn.disabled:hover {
  background: #f5f5f5;
  border-color: #e5e5e5;
  transform: none;
  box-shadow: none;
}

.tag-count {
  font-size: 0.85em;
  opacity: 0.7;
  margin-left: 0.25rem;
}

.material-btn {
  @extend .btn;
  @extend .btn-primary;
  @extend .m-1;
}

.material-btn:hover {
  background: $secondary;
  transform: translateY(-2px);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.material-btn.active {
  background: #ffd700;
  border-color: #ffd700;
  color: #000;
  font-weight: 600;
}

.material-btn.active:hover {
  background: #ffed4e;
  border-color: #ffed4e;
}

.material-btn.disabled {
  background: #f5f5f5;
  color: #ccc;
  border-color: #e5e5e5;
  cursor: not-allowed;
  opacity: 0.5;
}

.material-btn.disabled:hover {
  background: #f5f5f5;
  border-color: #e5e5e5;
  transform: none;
  box-shadow: none;
}

.material-count {
  font-size: 0.85em;
  opacity: 0.7;
  margin-left: 0.25rem;
}

.results-count {
  width: 100%;
  text-align: center;
  color: #666;
  font-size: 0.95rem;
  padding: 0.5rem;
}

/* Mobile Responsive */
@media (max-width: 768px) {
  .category-menu {
    gap: 0.5rem;
    padding-bottom: 1rem;
  }

  .category-btn {
    min-width: 80px;
    padding: 0.75rem 0.5rem;
  }

  .category-icon {
    width: 36px;
    height: 36px;
  }

  .category-name {
    font-size: 0.8rem;
  }

  .filters-container {
          @include make-container();
  }



  .additional-filters {
      @include make-row();
    }
  }

  .color-swatch {
    width: 40px;
    height: 40px;
  }

  .tag-btn {
    padding: 0.4rem 0.75rem;
    font-size: 0.9rem;
  }
}
</style>
