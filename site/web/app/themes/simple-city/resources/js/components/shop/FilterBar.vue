<template>
  <div ref="sentinelEl" class="filter-bar-sentinel" aria-hidden="true"></div>
  <div
    class="filter-bar"
    :class="{ 'is-stuck': isStuck, 'hide-filters-mode': props.hideFilters }"
    ref="filterBarEl"
  >
    <!-- Category Menu with Icons -->
    <div class="category-menu">
      <div class="categories-wrapper">
        <button
          class="category-btn"
          :class="{ active: showOnSale }"
          :style="{ '--category-bg-image': `url(${salesIcon})` }"
          @click="toggleOnSale"
        >
          <span class="category-name">Προσφορές</span>
        </button>
        <button
          v-for="category in shopStore.categories"
          :key="category.id"
          class="category-btn"
          :class="{ active: selectedCategory === category.id, 'no-icon': !getCategoryIcon(category.slug) }"
          :style="categoryBtnStyle(category.slug)"
          @click="selectCategory(category.id)">
          <span class="category-name">{{ category.name }}</span>
          <span v-if="category.count !== undefined" class="category-count-badge">{{ category.count }}</span>
        </button>
      </div>

        				<!-- Hide-filters template: tags appear here when a category is selected -->
				<div v-if="props.hideFilters && selectedCategory !== null && shopStore.tags.length > 0" class="tag-cloud-inline">
					<div class="cloud-inner">
						<button
							v-for="tag in stableSortedTags"
							:key="tag.id"
							class="tag-btn"
							:class="{ active: shopStore.filters.tags.includes(tag.id) }"
							@click="shopStore.setSingleTag(tag.id)"
						>
							{{ tag.name }} <span class="tag-count">({{ tag.count }})</span>
						</button>
					</div>
				</div>
      
    </div>

    <!-- Filters Container (Two Columns) -->
    <div
      v-if="!props.hideFilters"
      class="filters-container"
      :class="{
        'filters-open': filtersOpen,
        'has-category': selectedCategory !== null,
      }"
    >
      <!-- Tag Cloud (Left Column) -->
      <div v-if="shopStore.tags.length > 0" class="tag-cloud">
        <div
          class="cloud-inner"
          :style="{ maxHeight: tagsExpanded ? '2000px' : COLLAPSE_ROWS_HEIGHT }"
        >
          <button
            v-for="tag in sortedTags"
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
        <button class="cloud-toggle-btn" @click="tagsExpanded = !tagsExpanded">
          {{ tagsExpanded ? 'Λιγότερα ▲' : 'Περισσότερα ▼' }}
        </button>
      </div>

      <!-- Υλικά Column -->
      <div v-if="shopStore.materials.length > 0" class="material">
        <div
          class="cloud-inner"
          :style="{ maxHeight: materialsExpanded ? '2000px' : COLLAPSE_ROWS_HEIGHT }"
        >
          <button
            v-for="material in sortedMaterials"
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
        <button class="cloud-toggle-btn" @click="materialsExpanded = !materialsExpanded">
          {{ materialsExpanded ? 'Λιγότερα ▲' : 'Περισσότερα ▼' }}
        </button>
      </div>
      <!-- Extra Filters (Right Side) -->
      <div class="extra-filters">
        <div class="row">
          <!-- Price Range Slider -->
          <div class="price-filter">
            <div v-if="isPriceRangeRestricted" class="price-range-info">
              Διαθέσιμο: {{ shopStore.priceRange.filteredMin.toFixed(2) }}€ - {{ shopStore.priceRange.filteredMax.toFixed(2) }}€
            </div>

            <div class="price-slider">
              <v-range-slider
                v-model="priceRange"
                :min="shopStore.priceRange.min"
                :max="shopStore.priceRange.max"
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

          <!-- Color Filters -->
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
                :style="{ backgroundColor: color.hex || color.slug }"
                :title="color.name"
                :disabled="color.available === false && !shopStore.filters.colors.includes(color.id)"
                @click="toggleColor(color.id)"
              >
              </button>
            </div>
          </div>

          <!-- Height Filter -->
          <div v-if="shopStore.heights.length > 0" class="height-filter">
            <v-select
              v-model="selectedHeight"
              :items="heightItems"
              label="Ύψος"
              variant="outlined"
              density="compact"
              hide-details
              clearable
              @update:model-value="onHeightChange"
            ></v-select>
          </div>

          <!-- Width Filter -->
          <div v-if="shopStore.widths.length > 0" class="width-filter">
            <v-select
              v-model="selectedWidth"
              :items="widthItems"
              label="Πλάτος"
              variant="outlined"
              density="compact"
              hide-details
              clearable
              @update:model-value="onWidthChange"
            ></v-select>
          </div>

          <!-- Depth Filter -->
          <div v-if="shopStore.depths.length > 0" class="depth-filter">
            <v-select
              v-model="selectedDepth"
              :items="depthItems"
              label="Μήκος"
              variant="outlined"
              density="compact"
              hide-details
              clearable
              @update:model-value="onDepthChange"
            ></v-select>
          </div>
        </div>
      </div>

    </div>

    <div class="views">
  
			<div class="row justify-content-between">
			<div class="left col-auto">
				<button id="grid_2" data-value="view_small"> </button>
				<button id="grid_4" class="selected" data-value="view_normal"></button>
				<button id="grid_6" data-value="view_large"></button>
			</div>

			<!-- Results Count / Tags (hide-filters template) -->
			<div class="results-count col-auto">
			</div>

			<div class="right col-auto">
				<v-select
					id="sort-select"
					v-model="sortValue"
					:items="sortItems"
					variant="outlined"
					density="compact"
					hide-details
					@update:model-value="onSortChange"
				></v-select>
			</div>
			</div>
		</div>

  </div>

  <!-- Scroll to top — εμφανίζεται μόνο όταν το filter-bar είναι stuck -->
  <Teleport to="body">
    <button
      v-show="isStuck"
      class="scroll-to-top-btn"
      @click="scrollToTop"
      aria-label="Πάνω"
    >
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
        <polyline points="18 15 12 9 6 15"></polyline>
      </svg>
    </button>
  </Teleport>
</template>

<script setup>
import { ref, watch, onMounted, onUnmounted, computed } from 'vue';
import { useShopStore } from '../../stores/shop';

// Import SVG icons (ASCII filenames; mapped to Greek WP slugs in categoryIcons below)
import iconKathistiko from '../../../images/kathistiko.svg';
import iconYpnodomatio from '../../../images/ypnodomatio.svg';
import iconGrafeio from '../../../images/grafeio.svg';
import iconOrganosi from '../../../images/organosi.svg';
import iconDiakosmisi from '../../../images/diakosmisi.svg';
import iconExoterikos from '../../../images/exoterikos-choros.svg';
import iconMpanio from '../../../images/mpanio.svg';
import iconKouzina from '../../../images/kitchen.svg';
import salesIcon from '../../../images/sales.svg';

const props = defineProps({
  hideFilters: { type: Boolean, default: false },
});

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
const sortValue = ref('date-desc');
let priceUpdateTimeout = null;
let headerResizeObserver = null;
let _priceChangePending = false;
let _priceChangeTimer  = null;

const filterBarEl = ref(null);
const sentinelEl = ref(null);
const isStuck = ref(false);
const filtersOpen = ref(false);
const tagsExpanded = ref(false);
const materialsExpanded = ref(false);
const COLLAPSE_ROWS_HEIGHT = '105px';

let stickyObserver = null;

const setupStickyObserver = () => {
  if (stickyObserver) stickyObserver.disconnect();
  const headerHeight = parseFloat(
    getComputedStyle(document.documentElement).getPropertyValue('--header-height')
  ) || 80;
  // Hysteresis: sentinel has measurable height (see .filter-bar-sentinel CSS) so
  // intersectionRatio yields true partial values. We only toggle at the extremes,
  // giving a stable dead-zone around the boundary that absorbs wheel-momentum
  // oscillations.
  stickyObserver = new IntersectionObserver(
    ([entry]) => {
      if (entry.intersectionRatio <= 0) {
        if (!isStuck.value) isStuck.value = true;
      } else if (entry.intersectionRatio >= 1) {
        if (isStuck.value) {
          isStuck.value = false;
          filtersOpen.value = false;
        }
      }
    },
    {
      rootMargin: `-${Math.ceil(headerHeight)}px 0px 0px 0px`,
      threshold: [0, 1],
    }
  );
  if (sentinelEl.value) stickyObserver.observe(sentinelEl.value);
};

const syncHeaderHeight = () => {
  const header = document.querySelector('.head02');
  if (header) {
    document.documentElement.style.setProperty('--header-height', `${header.offsetHeight}px`);
    setupStickyObserver();
  }
};

// Sync local state with store on mount (for URL initialization)
onMounted(() => {
  selectedCategory.value = shopStore.filters.category;
  showOnSale.value = shopStore.filters.onSale;
  selectedTags.value = [...shopStore.filters.tags];
  selectedHeight.value = shopStore.filters.height;
  selectedWidth.value = shopStore.filters.width;
  selectedDepth.value = shopStore.filters.depth;
  sortValue.value = `${shopStore.filters.orderby}-${shopStore.filters.order}`;
  localMinPrice.value = shopStore.filters.minPrice ?? shopStore.priceRange.min;
  localMaxPrice.value = shopStore.filters.maxPrice ?? shopStore.priceRange.max;
  priceRange.value = [localMinPrice.value, localMaxPrice.value];

  // Sticky detection: sync header height and set up IntersectionObserver on sentinel
  syncHeaderHeight();
  const header = document.querySelector('.head02');
  if (header) {
    headerResizeObserver = new ResizeObserver(syncHeaderHeight);
    headerResizeObserver.observe(header);
  }
});

onUnmounted(() => {
  if (stickyObserver) stickyObserver.disconnect();
  if (headerResizeObserver) headerResizeObserver.disconnect();
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

// Watch sort changes from store
watch(() => `${shopStore.filters.orderby}-${shopStore.filters.order}`, (newVal) => {
  sortValue.value = newVal;
});

// Watch overall bounds — resets slider when category changes (min/max change completely)
watch(() => [shopStore.priceRange.min, shopStore.priceRange.max], ([min, max]) => {
  const currentMin = shopStore.filters.minPrice;
  const currentMax = shopStore.filters.maxPrice;
  localMinPrice.value = (currentMin !== null && currentMin >= min) ? currentMin : min;
  localMaxPrice.value = (currentMax !== null && currentMax <= max) ? currentMax : max;
  priceRange.value = [localMinPrice.value, localMaxPrice.value];
});

// Watch available range — auto-snaps handles when another filter narrows the price range.
// Skipped while the user is dragging the price slider itself (_priceChangePending flag).
watch(() => [shopStore.priceRange.filteredMin, shopStore.priceRange.filteredMax], ([filteredMin, filteredMax]) => {
  if (_priceChangePending) return;
  localMinPrice.value = filteredMin;
  localMaxPrice.value = filteredMax;
  priceRange.value = [filteredMin, filteredMax];
});

// Computed property to check if current range is outside filtered range
const isPriceRangeRestricted = computed(() => {
  return shopStore.priceRange.filteredMin > shopStore.priceRange.min ||
         shopStore.priceRange.filteredMax < shopStore.priceRange.max;
});

// Computed properties for v-select items
const heightItems = computed(() => {
  return shopStore.heights.map(height => ({
    title: `${height.name} (${height.count})`,
    value: height.slug,
    disabled: height.available === false
  }));
});

const widthItems = computed(() => {
  return shopStore.widths.map(width => ({
    title: `${width.name} (${width.count})`,
    value: width.slug,
    disabled: width.available === false
  }));
});

const depthItems = computed(() => {
  return shopStore.depths.map(depth => ({
    title: `${depth.name} (${depth.count})`,
    value: depth.slug,
    disabled: depth.available === false
  }));
});

// Sort items for v-select
const sortItems = computed(() => {
  return [
    { title: 'Προεπιλεγμένη ταξινόμηση', value: 'menu_order-asc' },
    { title: 'Δημοφιλή', value: 'popularity-desc' },
    { title: 'Πιο πρόσφατα', value: 'date-desc' },
    { title: 'Τιμή: Χαμηλή προς Υψηλή', value: 'price-asc' },
    { title: 'Τιμή: Υψηλή προς Χαμηλή', value: 'price-desc' }
  ];
});

// Category icon mapping — keyed by WP category slug.
// Greek slugs are the source of truth (per new WP setup); Latin aliases kept for safety.
const categoryIcons = {
  // Σαλόνι - Καθιστικό (slug: καθιστικό)
  'καθιστικό': iconKathistiko,
  'σαλόνι-καθιστικό': iconKathistiko,
  'saloni-kathistiko': iconKathistiko,
  'kathistiko': iconKathistiko,
  // Υπνοδωμάτιο
  'υπνοδωμάτιο': iconYpnodomatio,
  'ypnodomatio': iconYpnodomatio,
  'bedroom': iconYpnodomatio,
  // Γραφείο
  'γραφείο': iconGrafeio,
  'grafeio': iconGrafeio,
  'office': iconGrafeio,
  // Οργάνωση
  'οργάνωση': iconOrganosi,
  'organosi': iconOrganosi,
  // Διακόσμηση
  'διακόσμηση': iconDiakosmisi,
  'diakosmisi': iconDiakosmisi,
  // Εξωτερικός Χώρος
  'εξωτερικός-χώρος': iconExoterikos,
  'exoterikos-choros': iconExoterikos,
  'kipos-exoterikos': iconExoterikos,
  'garden': iconExoterikos,
  // Μπάνιο
  'μπάνιο': iconMpanio,
  'mpanio': iconMpanio,
  'bathroom': iconMpanio,
  // Κουζίνα
  'κουζίνα': iconKouzina,
  'kouzina': iconKouzina,
  'kitchen': iconKouzina,
};

const getCategoryIcon = (slug) => {
  if (!slug) return null;
  // WP REST επιστρέφει τα Greek slugs URL-encoded (π.χ. %ce%b3%cf%81%ce%b1%cf%86%ce%b5%ce%af%ce%bf).
  let decoded = slug;
  try {
    decoded = decodeURIComponent(slug);
  } catch (e) {
    // Αν το slug δεν είναι valid percent-encoded, κράτα το όπως ήρθε.
  }
  return categoryIcons[decoded] || categoryIcons[slug] || null;
};

const categoryBtnStyle = (slug) => {
  const icon = getCategoryIcon(slug);
  return icon ? { '--category-bg-image': `url(${icon})` } : {};
};

// Scroll the page so the filter-bar reaches its sticky (stuck) position.
// No-op when the bar is already stuck.
const scrollToStuck = () => {
  if (!filterBarEl.value) return;
  const headerHeight = parseFloat(
    getComputedStyle(document.documentElement).getPropertyValue('--header-height')
  ) || 80;
  const rect = filterBarEl.value.getBoundingClientRect();
  if (rect.top > headerHeight) {
    window.scrollTo({
      top: window.scrollY + rect.top - headerHeight + 1,
      behavior: 'smooth',
    });
  }
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
  scrollToStuck();
};

const toggleOnSale = () => {
  showOnSale.value = !showOnSale.value;
  shopStore.setOnSale(showOnSale.value);
  scrollToStuck();
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

const onHeightChange = (value) => {
  shopStore.setHeight(value);
};

const onWidthChange = (value) => {
  shopStore.setWidth(value);
};

const onDepthChange = (value) => {
  shopStore.setDepth(value);
};

const onSortChange = (value) => {
  const [orderby, order] = value.split('-');
  shopStore.setSort(orderby, order);
};

const onPriceRangeChange = (value) => {
  localMinPrice.value = value[0];
  localMaxPrice.value = value[1];

  // Prevent the filteredMin/Max watch from auto-snapping while the user is dragging.
  // The flag stays active for 700ms (500ms debounce + buffer) so the store response
  // doesn't reset the slider position mid-drag.
  clearTimeout(_priceChangeTimer);
  _priceChangePending = true;
  debouncedPriceUpdate();
  _priceChangeTimer = setTimeout(() => { _priceChangePending = false; }, 700);
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

const activeFilters = computed(() => {
  const chips = [];
  const f = shopStore.filters;

  if (f.onSale) {
    chips.push({ key: 'onSale', label: 'Προσφορές', action: () => shopStore.setOnSale(false) });
  }

  if (f.category) {
    const cat = shopStore.categories.find(c => c.id === f.category);
    chips.push({ key: `cat-${f.category}`, label: cat ? cat.name : `Κατηγορία #${f.category}`, action: () => shopStore.setCategory(null) });
  }

  for (const id of f.tags) {
    const tag = shopStore.tags.find(t => t.id === id);
    chips.push({ key: `tag-${id}`, label: tag ? tag.name : `Tag #${id}`, action: () => shopStore.toggleTag(id) });
  }

  for (const id of f.colors) {
    const color = shopStore.colors.find(c => c.id === id);
    chips.push({ key: `color-${id}`, label: color ? color.name : `Χρώμα #${id}`, action: () => shopStore.toggleColor(id) });
  }

  for (const id of f.materials) {
    const material = shopStore.materials.find(m => m.id === id);
    chips.push({ key: `mat-${id}`, label: material ? material.name : `Υλικό #${id}`, action: () => shopStore.toggleMaterial(id) });
  }

  if (f.height) {
    const h = shopStore.heights.find(item => item.slug === f.height);
    chips.push({ key: 'height', label: `Ύψος: ${h ? h.name : f.height}`, action: () => { shopStore.setHeight(null); selectedHeight.value = null; } });
  }

  if (f.width) {
    const w = shopStore.widths.find(item => item.slug === f.width);
    chips.push({ key: 'width', label: `Πλάτος: ${w ? w.name : f.width}`, action: () => { shopStore.setWidth(null); selectedWidth.value = null; } });
  }

  if (f.depth) {
    const d = shopStore.depths.find(item => item.slug === f.depth);
    chips.push({ key: 'depth', label: `Μήκος: ${d ? d.name : f.depth}`, action: () => { shopStore.setDepth(null); selectedDepth.value = null; } });
  }

  if (
    f.minPrice !== null && f.maxPrice !== null &&
    (f.minPrice > shopStore.priceRange.min || f.maxPrice < shopStore.priceRange.max)
  ) {
    chips.push({
      key: 'price',
      label: `${f.minPrice}€ – ${f.maxPrice}€`,
      action: () => shopStore.setPriceRange(shopStore.priceRange.min, shopStore.priceRange.max)
    });
  }

  if (f.search) {
    chips.push({ key: 'search', label: `"${f.search}"`, action: () => shopStore.setSearch('') });
  }

  return chips;
});

const sortedTags = computed(() =>
  [...shopStore.tags].sort((a, b) => b.count - a.count)
);

// Hide-filters mode: stable tag order captured on first load after a category
// change. Stops the cloud from re-shuffling each time a tag is clicked (counts
// change → default sortedTags would re-sort).
const stableTagOrder = ref([]);

watch(() => shopStore.filters.category, () => {
  stableTagOrder.value = [];
});

watch(
  () => shopStore.tags,
  (newTags) => {
    if (!props.hideFilters) return;
    if (stableTagOrder.value.length === 0 && newTags.length > 0) {
      stableTagOrder.value = [...newTags]
        .sort((a, b) => b.count - a.count)
        .map(t => t.id);
    }
  },
  { immediate: true }
);

const stableSortedTags = computed(() => {
  if (!props.hideFilters || stableTagOrder.value.length === 0) {
    return sortedTags.value;
  }
  const tagsById = new Map(shopStore.tags.map(t => [t.id, t]));
  return stableTagOrder.value
    .map(id => tagsById.get(id))
    .filter(Boolean);
});

const sortedMaterials = computed(() => {
  return [...shopStore.materials].sort((a, b) => {
    const aAvail = a.available === false ? 1 : 0;
    const bAvail = b.available === false ? 1 : 0;
    if (aAvail !== bAvail) return aAvail - bAvail;
    return b.count - a.count;
  });
});

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

const scrollToTop = () => {
  window.scrollTo({ top: 0, behavior: 'smooth' });
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

.filter-bar-sentinel {
  /* Tall sentinel provides hysteresis dead-zone for the IntersectionObserver
     (ratio 0 ↔ 1), preventing flicker around the stuck boundary. */
  height: 40px;
  margin-bottom: -40px;
  visibility: hidden;
  pointer-events: none;
}

.filter-bar {
  @include make-row();

  position: sticky;
  top: var(--header-height, 80px);
  z-index: 200;
  background: white;
}

/* Category Menu */
.category-menu {
      @include make-container();
      max-width: 1440px;
      padding-top: 1rem;
      padding-bottom: 3rem;
      transition: padding 0.12s cubic-bezier(0.2, 0, 0.2, 1);

    & .categories-wrapper {
      @include make-row();
      @extend .justify-content-between;
    }

    & .tag-cloud-inline {
      @include make-row();
      @extend .justify-content-center;
      @extend .mt-4;
    }
}

.filter-bar.is-stuck .category-menu {
  padding-top: 4.5rem;
  padding-bottom: 0.5rem;
}

/* Results count: toggle between text and button */
.results-count {
  display: flex;
  align-items: center;
  justify-content: center;
}

/* Hide-filters template: override the make-col(4) widths defined in
   sections.scss for .shop .views .{left,results-count,right} so the
   three cols size to their content instead of a fixed 33% column. */
.filter-bar.hide-filters-mode .views {
  & .row {
    flex-wrap: inherit;
  }

  & .left,
  & .results-count,
  & .right {
    flex: 0 0 auto;
    width: auto;
    max-width: none;
  }
}

/* Inline tag cloud — used on the hide-filters template,
   rendered inside .results-count when a category is selected */
.tag-cloud-inline {
  width: 100%;
  text-align: center;

  & .cloud-inner {
    @extend .d-flex;
    flex-wrap: wrap;
    @extend .justify-content-center;
    overflow-x: auto;
    overflow-y: hidden;
  }

  & .tag-btn {
    @extend .btn;
    @include button-variant($third, $third);
    background-color: transparent;
    border: 1px solid $fourth;
    margin: 0.05rem;
    flex: 0 0 auto;
    white-space: nowrap;
    position: relative;
  }

  & .tag-btn:hover {
    background: #f5f5f5;
    transform: translateY(-2px);
  }

  & .tag-btn.active {
    background: #f0f0f0;
    border-color: $primary;
  }

  & .tag-btn.active::after {
    content: '';
    position: absolute;
    bottom: -1rem;
    left: 50%;
    transform: translateX(-50%);
    width: 60%;
    height: 3px;
    background: $secondary;
  }

  & .tag-btn.active:hover {
    background: #f0f0f0;
  }

  & .tag-btn.disabled {
    background: #f5f5f5;
    color: #ccc;
    border-color: #e5e5e5;
    cursor: not-allowed;
    opacity: 0.5;
  }

  & .tag-btn.disabled:hover {
    background: #f5f5f5;
    border-color: #e5e5e5;
    transform: none;
    box-shadow: none;
  }

  & .tag-count {
    font-size: 0.85em;
    opacity: 0.7;
    margin-left: 0.25rem;
  }

  & .cloud-toggle-btn {
    margin-top: 0.5rem;
    padding: 0.2rem 0.75rem;
    font-size: 0.78rem;
    background: transparent;
    border: 1px solid #ccc;
    cursor: pointer;
    color: #555;
    transition: all 0.2s;

    &:hover {
      border-color: #000;
      color: #000;
    }
  }
}

.results-text {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.4rem;
}

.filter-chip {
  display: inline-flex;
  align-items: center;
  gap: 0.3rem;
  padding: 0.2rem 0.6rem;
  background: #ffd700;
  border: 1px solid #ffd700;
  border-radius: 2rem;
  font-size: 0.8rem;
  font-weight: 600;
  color: #000;
  white-space: nowrap;
}

.filter-chip-remove {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 1.1rem;
  height: 1.1rem;
  padding: 0;
  background: #888;
  color: #fff;
  border: none;
  border-radius: 50%;
  font-size: 0.75rem;
  line-height: 1;
  cursor: pointer;
  transition: background 0.15s;

  &:hover {
    background: #0E0C0A;
  }
}

.filter-clear-all {
  display: inline-flex;
  align-items: center;
  padding: 0.2rem 0.75rem;
  background: transparent;
  color: #555;
  border: 1px solid #bbb;
  border-radius: 2rem;
  font-size: 0.8rem;
  cursor: pointer;
  transition: border-color 0.15s, color 0.15s;

  &:hover {
    border-color: #333;
    color: #000;
  }
}

/* Filters container collapse — single max-height + opacity transition.
   Avoid transform: scaleY on a child of a sticky parent (causes paint glitches
   on desktop Chrome/Safari) and avoid the instant max-height snap which jumped
   layout below the bar. Both states share the same transition so closing and
   opening animate symmetrically. */
.filters-container {
  max-height: 0;
  overflow: hidden;
  opacity: 0;
  pointer-events: none;
  transition:
    max-height 0.22s cubic-bezier(0.2, 0, 0.2, 1),
    opacity 0.15s cubic-bezier(0.2, 0, 0.2, 1);
  will-change: max-height, opacity;
}

.filters-container.has-category {
  max-height: 2000px;
  opacity: 1;
  pointer-events: auto;
}

.filter-bar.is-stuck .filters-container {
  max-height: 0;
  opacity: 0;
  pointer-events: none;
}

.filter-bar.is-stuck .filters-container.filters-open {
  max-height: 2000px;
  opacity: 1;
  pointer-events: auto;
}

.category-btn {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
  padding: calc(48px + 1.5rem) 1rem 1rem;
  background-color: transparent;
  background-image: var(--category-bg-image, none);
  background-repeat: no-repeat;
  background-position: center 1rem;
  background-size: 68px 68px;
  border: none;
  cursor: pointer;
  transition: all 0.3s ease;
  border-radius: 8px;
  width: 150px;
  flex: 0 0 150px;
  position: relative;
  border: 1px $fourth solid;
}

.category-btn.no-icon {
  padding: 1rem;
}

.category-btn:hover {
  background-color: #f5f5f5;
  transform: translateY(-2px);
}

.category-btn.active {
  background-color: #f0f0f0;
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

/* Stuck (sticky) state: shrink category buttons vertically */
.filter-bar.is-stuck .category-btn {
  padding: calc(32px + 0.5rem) 0.75rem 0.5rem;
  background-size: 32px 32px;
  background-position: center 0.4rem;
}

.filter-bar.is-stuck .category-btn.no-icon {
  padding: 0.5rem 0.75rem;
}

.filter-bar.is-stuck .category-name {
  font-size: 0.8rem;
}

.filter-bar.is-stuck .category-count-badge {
  min-width: 1.1rem;
  height: 1.1rem;
  font-size: 0.65rem;
  top: 0.15rem;
  right: 0.15rem;
}

.category-name {
  font-size: 0.9rem;
  font-weight: 500;
  text-align: center;
  white-space: normal;
  word-wrap: break-word;
  overflow-wrap: break-word;
}

.category-count-badge {
  position: absolute;
  top: 0.25rem;
  right: 0.25rem;
  min-width: 1.25rem;
  height: 1.25rem;
  padding: 0 0.35rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 0.7rem;
  font-weight: 600;
  line-height: 1;
  color: $primary;
  background-color: $secondary;
  border-radius: 999px;
  pointer-events: none;
}

/* Filters Container (Two Columns) */
.filters-container {
  @include make-row();
  @extend .w-100;

/* Tag Cloud (Left Column - 50%) */
    & .tag-cloud {
        @include make-col-ready();
        @include media-breakpoint-up(lg) {
        @include make-col(6);
      }
      @extend .pt-4;
  }

/* Material Column */
    & .material {
      @include make-col-ready();
      @include media-breakpoint-up(lg) {
      @include make-col(3);
    }
      @extend .pt-4;
      @extend .pe-0;

}

/* Additional Filters (Right Column - 50%) */
    & .additional-filters {
      @include make-col-ready();
      @include media-breakpoint-up(lg) {
      @include make-col(12);
    }
}

/* Color Filters (Left Side of Additional Filters) */
    & .color-filters {
      @include make-col-ready();
    
      @include media-breakpoint-up(md) {
      @include make-col(12);
      }
}

    & .height-filter {
    @include make-col-ready();

    @include media-breakpoint-up(sm) {
    @include make-col(4);
    }
    @include media-breakpoint-up(lg) {
    @include make-col(4);
    }
}

    & .width-filter {
    @include make-col-ready();

    @include media-breakpoint-up(sm) {
    @include make-col(4);
    }
    @include media-breakpoint-up(lg) {
    @include make-col(4);
    }
}

    & .depth-filter {
    @include make-col-ready();

    @include media-breakpoint-up(sm) {
    @include make-col(4);
    }
    @include media-breakpoint-up(lg) {
    @include make-col(4);
    }
}

// Vuetify v-select styling
    & .height-filter,
    & .width-filter,
    & .depth-filter {
      :deep(.v-label) {
        font-size: 0.85rem;
      }
    }

    & .extra-filters {
      @include make-col-ready();
      @include media-breakpoint-up(lg) {
      @include make-col(3);
    }
    @extend .ps-3;
    @extend .pe-0;
}

/* Price Filter */
    & .price-filter {
      @include make-col-ready();

      @include media-breakpoint-up(md) {
      @include make-col(12);
      }
      @extend .pb-4;
  }

    & .price-range-info {
      font-size: 0.85rem;
      color: #666;
      background: #fff3cd;
      padding: 0.5rem;
      border-radius: 4px;
      text-align: center;
      border: 1px solid #ffd700;
    }

    & .price-inputs {
  display: flex;
  gap: 1rem;
  align-items: center;
}

    & .price-input-group {
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    & .price-input-group label {
  font-size: 0.9rem;
  color: #666;
  min-width: 40px;
}

    & .price-input {
      width: 80px;
      padding: 0.5rem;
      border: 2px solid #ddd;
      border-radius: 4px;
      font-size: 0.9rem;
      text-align: center;
    }

    & .price-input:focus {
      outline: none;
      border-color: #000;
    }

    & .price-input-group span {
      font-size: 0.9rem;
      color: #666;
    }

/* Price Slider */
    & .price-slider {
      :deep(.v-slider-thumb__label::after) {
        content: '€';
        margin-left: 2px;
      }
    }

    & .filter-title {
      font-size: 1rem;
      font-weight: 600;
      margin: 0;
      color: #333;
    }

    & .color-swatches {
      @include make-col-ready();
    
      @include media-breakpoint-up(md) {
      @include make-col(12);
      }
      @extend .p-0;
      @extend .mb-3;
}

    & .color-swatch {
      position: relative;
      width: 1.55rem;
      height: 1.55rem;
      border-radius: 50%;
      border: 2px solid #ddd;
      cursor: pointer;
      transition: all 0.3s ease;
      overflow: hidden;
      @extend .me-2;
    }

    & .color-swatch:hover {
      transform: scale(1.1);
      border-color: #999;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
    }

    & .color-swatch.active {
      border-color: #000;
      border-width: 4px;
      box-shadow: 0 0 0 2px #ffd700;
    }

    & .color-swatch.disabled {
      opacity: 0.3;
      cursor: not-allowed;
      filter: grayscale(50%);
    }

    & .color-swatch.disabled:hover {
      transform: none;
      border-color: #ddd;
      box-shadow: none;
    }

    & .color-name {
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

    & .color-swatch:hover .color-name {
      opacity: 1;
    }

    & .cloud-toggle-btn {
      margin-top: 0.5rem;
      padding: 0.2rem 0.75rem;
      font-size: 0.78rem;
      background: transparent;
      border: 1px solid #ccc;
      cursor: pointer;
      color: #555;
      transition: all 0.2s;

      &:hover {
        border-color: #000;
        color: #000;
      }
    }

    & .tag-btn {
      @extend .btn;
      @extend .btn-primary;
      background-color: transparent;
      border: 1px solid $fourth;
     // @extend .m-1;
     margin: 0.05rem;
     position: relative;
    }

    & .tag-btn:hover {
      background: #f5f5f5;
      transform: translateY(-2px);
    }

    & .tag-btn.active {
      background: #f0f0f0;
    }

    & .tag-btn.active::after {
      content: '';
      position: absolute;
      bottom: -1rem;
      left: 50%;
      transform: translateX(-50%);
      width: 60%;
      height: 3px;
      background: $secondary;
    }

    & .tag-btn.active:hover {
      background: #f0f0f0;
    }

    & .tag-btn.disabled {
      background: #f5f5f5;
      color: #ccc;
      border-color: #e5e5e5;
      cursor: not-allowed;
      opacity: 0.5;
    }

    & .tag-btn.disabled:hover {
      background: #f5f5f5;
      border-color: #e5e5e5;
      transform: none;
      box-shadow: none;
    }

    & .tag-count {
      font-size: 0.85em;
      opacity: 0.7;
      margin-left: 0.25rem;
    }

    & .material-btn {
      @extend .btn;
      @extend .btn-primary;
      //@extend .m-1;
      margin: 0.05rem;
    }

    & .material-btn:hover {
      background: $secondary;
      transform: translateY(-2px);
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    & .material-btn.active {
      background: #ffd700;
      border-color: #ffd700;
      color: #000;
      font-weight: 600;
    }

    & .material-btn.active:hover {
      background: #ffed4e;
      border-color: #ffed4e;
    }

    & .material-btn.disabled {
      background: #f5f5f5;
      color: #ccc;
      border-color: #e5e5e5;
      cursor: not-allowed;
      opacity: 0.5;
    }

    & .material-btn.disabled:hover {
      background: #f5f5f5;
      border-color: #e5e5e5;
      transform: none;
      box-shadow: none;
    }

    & .material-count {
      font-size: 0.85em;
      opacity: 0.7;
      margin-left: 0.25rem;
    }



/* Mobile Responsive */
@media (max-width: 768px) {
  .category-menu {
    gap: 0.5rem;
    padding-bottom: 1rem;
  }

  .category-btn {
    min-width: 80px;
    padding: calc(36px + 1.25rem) 0.5rem 0.75rem;
    background-size: 36px 36px;
    background-position: center 0.5rem;
  }

  .category-btn.no-icon {
    padding: 0.75rem 0.5rem;
  }

  .category-name {
    font-size: 0.8rem;
  }





  .additional-filters {
      @include make-row();
    }
  }



  .tag-btn {
    padding: 0.4rem 0.75rem;
    font-size: 0.9rem;
  }
}
</style>

<style lang="scss">
@import '../../../css/custom/shared-variables';

.scroll-to-top-btn {
  position: fixed;
  bottom: 2rem;
  right: 2rem;
  width: 3rem;
  height: 3rem;
  border-radius: 50%;
  background: $primary;
  color: $secondary;
  border: none;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  box-shadow: 0 4px 14px rgba(0, 0, 0, 0.35);
  transition: transform 0.2s ease, box-shadow 0.2s ease;

  svg {
    width: 1.25rem;
    height: 1.25rem;
  }

  &:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 18px rgba(0, 0, 0, 0.45);
  }
}
</style>
