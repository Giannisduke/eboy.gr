import axios from 'axios';

/**
 * Products API Service
 */
export const productsApi = {
    /**
     * Fetch products with filters
     */
    async getProducts(filters = {}) {
        const params = {
            per_page: filters.perPage || 10,
            page: filters.page || 1,
            orderby: filters.orderby || 'menu_order',
            order: filters.order || 'asc',
        };

        // Add category filter
        if (filters.category) {
            params.category = filters.category;
        }

        // Add search
        if (filters.search) {
            params.search = filters.search;
        }

        // Add sale filter
        if (filters.onSale) {
            params.on_sale = 'true';
        }

        try {
            const response = await axios.get('/wp-json/theme/v1/products', { params });

            return {
                products: response.data.products,
                total: response.data.total,
                totalPages: response.data.totalPages
            };
        } catch (error) {
            console.error('Error fetching products:', error);
            throw new Error('Failed to load products. Please try again.');
        }
    },

    /**
     * Get product categories
     */
    async getCategories() {
        try {
            const response = await axios.get('/wp-json/theme/v1/categories', {
                params: {
                    per_page: 100,
                    hide_empty: true,
                    parent: 0 // Only top-level categories
                }
            });
            return response.data;
        } catch (error) {
            console.error('Error fetching categories:', error);
            throw new Error('Failed to load categories. Please try again.');
        }
    }
};

export default productsApi;
