import { defineStore } from 'pinia';
import { productsApi } from '../services/products';

export const useShopStore = defineStore('shop', {
    state: () => ({
        products: [],
        categories: [],
        filters: {
            category: null,
            search: '',
            onSale: false,
            orderby: 'menu_order',
            order: 'asc',
            page: 1,
            perPage: 10
        },
        pagination: {
            total: 0,
            totalPages: 1,
            currentPage: 1
        },
        loading: false,
        error: null
    }),

    getters: {
        filteredProductsCount: (state) => state.pagination.total,
        hasProducts: (state) => state.products.length > 0,
        isFirstPage: (state) => state.pagination.currentPage === 1,
        isLastPage: (state) => state.pagination.currentPage === state.pagination.totalPages
    },

    actions: {
        async fetchProducts() {
            this.loading = true;
            this.error = null;

            try {
                const result = await productsApi.getProducts(this.filters);
                this.products = result.products;
                this.pagination.total = result.total;
                this.pagination.totalPages = result.totalPages;
                this.pagination.currentPage = this.filters.page;
            } catch (error) {
                this.error = error.message;
                console.error('Error fetching products:', error);
            } finally {
                this.loading = false;
            }
        },

        async fetchCategories() {
            try {
                this.categories = await productsApi.getCategories();
            } catch (error) {
                console.error('Error fetching categories:', error);
            }
        },

        setCategory(categoryId) {
            this.filters.category = categoryId;
            this.filters.page = 1; // Reset to first page
            this.fetchProducts();
        },

        setSearch(searchTerm) {
            this.filters.search = searchTerm;
            this.filters.page = 1;
            this.fetchProducts();
        },

        setOnSale(onSale) {
            this.filters.onSale = onSale;
            this.filters.page = 1;
            this.fetchProducts();
        },

        setSort(orderby, order = 'asc') {
            this.filters.orderby = orderby;
            this.filters.order = order;
            this.filters.page = 1;
            this.fetchProducts();
        },

        setPage(page) {
            this.filters.page = page;
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
                orderby: 'menu_order',
                order: 'asc',
                page: 1,
                perPage: 10
            };
            this.fetchProducts();
        }
    }
});
