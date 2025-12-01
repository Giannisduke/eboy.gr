import { defineStore } from 'pinia';
import { productsApi } from '../services/products';

export const useShopStore = defineStore('shop', {
    state: () => ({
        products: [],
        categories: [],
        tags: [],
        colors: [],
        materials: [],
        heights: [],
        widths: [],
        depths: [],
        priceRange: {
            min: 0,
            max: 1000,
            filteredMin: 0,
            filteredMax: 1000
        },
        filters: {
            category: null,
            search: '',
            onSale: false,
            tags: [],
            colors: [],
            materials: [],
            height: null,
            width: null,
            depth: null,
            minPrice: null,
            maxPrice: null,
            orderby: 'menu_order',
            order: 'asc',
            page: 1,
            perPage: 12
        },
        pagination: {
            total: 0,
            totalPages: 1,
            currentPage: 1
        },
        hasMore: true,
        gridColumns: 4, // 2, 4, or 6 columns
        loading: false,
        loadingMore: false,
        error: null
    }),

    getters: {
        filteredProductsCount: (state) => state.pagination.total,
        hasProducts: (state) => state.products.length > 0,
        isFirstPage: (state) => state.pagination.currentPage === 1,
        isLastPage: (state) => state.pagination.currentPage === state.pagination.totalPages
    },

    actions: {
        // Initialize filters from URL parameters
        initFromURL() {
            const params = new URLSearchParams(window.location.search);

            if (params.has('category')) {
                this.filters.category = parseInt(params.get('category')) || null;
            }
            if (params.has('search')) {
                this.filters.search = params.get('search');
            }
            if (params.has('on_sale')) {
                this.filters.onSale = params.get('on_sale') === 'true';
            }
            if (params.has('orderby')) {
                this.filters.orderby = params.get('orderby');
            }
            if (params.has('order')) {
                this.filters.order = params.get('order');
            }
            if (params.has('page')) {
                this.filters.page = parseInt(params.get('page')) || 1;
            }
            if (params.has('tags')) {
                const tagIds = params.get('tags').split(',').map(id => parseInt(id)).filter(id => !isNaN(id));
                this.filters.tags = tagIds;
            }
            if (params.has('colors')) {
                const colorIds = params.get('colors').split(',').map(id => parseInt(id)).filter(id => !isNaN(id));
                this.filters.colors = colorIds;
            }
            if (params.has('materials')) {
                const materialIds = params.get('materials').split(',').map(id => parseInt(id)).filter(id => !isNaN(id));
                this.filters.materials = materialIds;
            }
            if (params.has('min_price')) {
                this.filters.minPrice = parseFloat(params.get('min_price'));
            }
            if (params.has('max_price')) {
                this.filters.maxPrice = parseFloat(params.get('max_price'));
            }
            if (params.has('height')) {
                this.filters.height = params.get('height');
            }
            if (params.has('width')) {
                this.filters.width = params.get('width');
            }
            if (params.has('depth')) {
                this.filters.depth = params.get('depth');
            }
        },

        // Update URL with current filters
        updateURL() {
            const params = new URLSearchParams();

            if (this.filters.category) {
                params.set('category', this.filters.category);
            }
            if (this.filters.search) {
                params.set('search', this.filters.search);
            }
            if (this.filters.onSale) {
                params.set('on_sale', 'true');
            }
            if (this.filters.tags && this.filters.tags.length > 0) {
                params.set('tags', this.filters.tags.join(','));
            }
            if (this.filters.colors && this.filters.colors.length > 0) {
                params.set('colors', this.filters.colors.join(','));
            }
            if (this.filters.materials && this.filters.materials.length > 0) {
                params.set('materials', this.filters.materials.join(','));
            }
            if (this.filters.minPrice !== null && this.filters.minPrice !== undefined) {
                params.set('min_price', this.filters.minPrice);
            }
            if (this.filters.maxPrice !== null && this.filters.maxPrice !== undefined) {
                params.set('max_price', this.filters.maxPrice);
            }
            if (this.filters.height) {
                params.set('height', this.filters.height);
            }
            if (this.filters.width) {
                params.set('width', this.filters.width);
            }
            if (this.filters.depth) {
                params.set('depth', this.filters.depth);
            }
            if (this.filters.orderby !== 'menu_order') {
                params.set('orderby', this.filters.orderby);
            }
            if (this.filters.order !== 'asc') {
                params.set('order', this.filters.order);
            }
            if (this.filters.page > 1) {
                params.set('page', this.filters.page);
            }

            const newURL = params.toString()
                ? `${window.location.pathname}?${params.toString()}`
                : window.location.pathname;

            window.history.pushState({}, '', newURL);
        },

        async fetchProducts(append = false) {
            if (append) {
                this.loadingMore = true;
            } else {
                this.loading = true;
            }
            this.error = null;

            try {
                const result = await productsApi.getProducts(this.filters);

                if (append) {
                    this.products = [...this.products, ...result.products];
                } else {
                    this.products = result.products;
                }

                this.pagination.total = result.total;
                this.pagination.totalPages = result.totalPages;
                this.pagination.currentPage = this.filters.page;
                this.hasMore = this.filters.page < result.totalPages;
            } catch (error) {
                this.error = error.message;
                console.error('Error fetching products:', error);
            } finally {
                this.loading = false;
                this.loadingMore = false;
            }
        },

        async fetchCategories() {
            try {
                this.categories = await productsApi.getCategories();
            } catch (error) {
                console.error('Error fetching categories:', error);
            }
        },

        async fetchTags() {
            try {
                this.tags = await productsApi.getTags(this.filters);
            } catch (error) {
                console.error('Error fetching tags:', error);
            }
        },

        async fetchColors() {
            try {
                this.colors = await productsApi.getColors(this.filters);
            } catch (error) {
                console.error('Error fetching colors:', error);
            }
        },

        async fetchMaterials() {
            try {
                this.materials = await productsApi.getMaterials(this.filters);
            } catch (error) {
                console.error('Error fetching materials:', error);
            }
        },

        async fetchHeights() {
            try {
                this.heights = await productsApi.getHeights(this.filters);
            } catch (error) {
                console.error('Error fetching heights:', error);
            }
        },

        async fetchWidths() {
            try {
                this.widths = await productsApi.getWidths(this.filters);
            } catch (error) {
                console.error('Error fetching widths:', error);
            }
        },

        async fetchDepths() {
            try {
                this.depths = await productsApi.getDepths(this.filters);
            } catch (error) {
                console.error('Error fetching depths:', error);
            }
        },

        async fetchPriceRange() {
            try {
                const range = await productsApi.getPriceRange(this.filters);
                this.priceRange.min = range.min;
                this.priceRange.max = range.max;
                this.priceRange.filteredMin = range.filteredMin;
                this.priceRange.filteredMax = range.filteredMax;

                // Initialize filters if not set from URL
                if (this.filters.minPrice === null) {
                    this.filters.minPrice = range.min;
                }
                if (this.filters.maxPrice === null) {
                    this.filters.maxPrice = range.max;
                }
            } catch (error) {
                console.error('Error fetching price range:', error);
            }
        },

        async loadMoreProducts() {
            if (!this.hasMore || this.loadingMore) {
                return;
            }

            this.filters.page += 1;
            await this.fetchProducts(true);
        },

        async setCategory(categoryId) {
            this.filters.category = categoryId;
            this.filters.page = 1;

            // Clear tag and color filters when changing category
            this.filters.tags = [];
            this.filters.colors = [];
            this.filters.materials = [];
            this.filters.height = null;
            this.filters.width = null;
            this.filters.depth = null;

            // Reset price range to defaults
            this.filters.minPrice = null;
            this.filters.maxPrice = null;

            this.updateURL();

            // Re-fetch filters based on new category
            await this.fetchTags();
            await this.fetchColors();
            await this.fetchMaterials();
            await this.fetchHeights();
            await this.fetchWidths();
            await this.fetchDepths();
            await this.fetchPriceRange();

            // Fetch products with new category
            this.fetchProducts();
        },

        setSearch(searchTerm) {
            this.filters.search = searchTerm;
            this.filters.page = 1;
            this.updateURL();
            this.fetchProducts();
        },

        setOnSale(onSale) {
            this.filters.onSale = onSale;
            this.filters.page = 1;
            this.updateURL();
            this.fetchProducts();
        },

        async toggleTag(tagId) {
            const index = this.filters.tags.indexOf(tagId);
            if (index === -1) {
                // Add tag
                this.filters.tags.push(tagId);
            } else {
                // Remove tag
                this.filters.tags.splice(index, 1);
            }
            this.filters.page = 1;
            this.updateURL();

            // Re-fetch all filters to update availability
            await Promise.all([
                this.fetchTags(),
                this.fetchColors(),
                this.fetchMaterials(),
                this.fetchHeights(),
                this.fetchWidths(),
                this.fetchDepths(),
                this.fetchPriceRange()
            ]);

            this.fetchProducts();
        },

        async toggleColor(colorId) {
            const index = this.filters.colors.indexOf(colorId);
            if (index === -1) {
                // Add color
                this.filters.colors.push(colorId);
            } else {
                // Remove color
                this.filters.colors.splice(index, 1);
            }
            this.filters.page = 1;
            this.updateURL();

            // Re-fetch all filters to update availability
            await Promise.all([
                this.fetchTags(),
                this.fetchColors(),
                this.fetchMaterials(),
                this.fetchHeights(),
                this.fetchWidths(),
                this.fetchDepths(),
                this.fetchPriceRange()
            ]);

            this.fetchProducts();
        },

        async toggleMaterial(materialId) {
            const index = this.filters.materials.indexOf(materialId);
            if (index === -1) {
                // Add material
                this.filters.materials.push(materialId);
            } else {
                // Remove material
                this.filters.materials.splice(index, 1);
            }
            this.filters.page = 1;
            this.updateURL();

            // Re-fetch all filters to update availability
            await Promise.all([
                this.fetchTags(),
                this.fetchColors(),
                this.fetchMaterials(),
                this.fetchHeights(),
                this.fetchWidths(),
                this.fetchDepths(),
                this.fetchPriceRange()
            ]);

            this.fetchProducts();
        },

        async setHeight(height) {
            this.filters.height = height;
            this.filters.page = 1;
            this.updateURL();

            // Re-fetch all filters to update availability
            await Promise.all([
                this.fetchTags(),
                this.fetchColors(),
                this.fetchMaterials(),
                this.fetchHeights(),
                this.fetchWidths(),
                this.fetchDepths(),
                this.fetchPriceRange()
            ]);

            this.fetchProducts();
        },

        async setWidth(width) {
            this.filters.width = width;
            this.filters.page = 1;
            this.updateURL();

            // Re-fetch all filters to update availability
            await Promise.all([
                this.fetchTags(),
                this.fetchColors(),
                this.fetchMaterials(),
                this.fetchHeights(),
                this.fetchWidths(),
                this.fetchDepths(),
                this.fetchPriceRange()
            ]);

            this.fetchProducts();
        },

        async setDepth(depth) {
            this.filters.depth = depth;
            this.filters.page = 1;
            this.updateURL();

            // Re-fetch all filters to update availability
            await Promise.all([
                this.fetchTags(),
                this.fetchColors(),
                this.fetchMaterials(),
                this.fetchHeights(),
                this.fetchWidths(),
                this.fetchDepths(),
                this.fetchPriceRange()
            ]);

            this.fetchProducts();
        },

        async setPriceRange(minPrice, maxPrice) {
            this.filters.minPrice = minPrice;
            this.filters.maxPrice = maxPrice;
            this.filters.page = 1;
            this.updateURL();

            // Re-fetch all filters to update availability
            await Promise.all([
                this.fetchTags(),
                this.fetchColors(),
                this.fetchMaterials(),
                this.fetchHeights(),
                this.fetchWidths(),
                this.fetchDepths()
            ]);

            this.fetchProducts();
        },

        setSort(orderby, order = 'asc') {
            this.filters.orderby = orderby;
            this.filters.order = order;
            this.filters.page = 1;
            this.updateURL();
            this.fetchProducts();
        },

        setPage(page) {
            this.filters.page = page;
            this.updateURL();
            this.fetchProducts();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },

        nextPage() {
            if (!this.isLastPage) {
                this.setPage(this.pagination.currentPage + 1);
            }
        },

        prevPage() {
            if (!this.isFirstPage) {
                this.setPage(this.pagination.currentPage - 1);
            }
        },

        clearFilters() {
            this.filters = {
                category: null,
                search: '',
                onSale: false,
                tags: [],
                colors: [],
                materials: [],
                height: null,
                width: null,
                depth: null,
                minPrice: this.priceRange.min,
                maxPrice: this.priceRange.max,
                orderby: 'menu_order',
                order: 'asc',
                page: 1,
                perPage: 12
            };
            this.updateURL();
            this.fetchProducts();
        },

        setGridColumns(columns) {
            this.gridColumns = columns;
            // Save to localStorage
            localStorage.setItem('shopGridColumns', columns);
        },

        initGridColumns() {
            // Load from localStorage if available
            const saved = localStorage.getItem('shopGridColumns');
            if (saved) {
                this.gridColumns = parseInt(saved);
            }
        }
    }
});
