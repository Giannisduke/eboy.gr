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

        // Add tags filter
        if (filters.tags && filters.tags.length > 0) {
            params.tags = filters.tags.join(',');
        }

        // Add colors filter
        if (filters.colors && filters.colors.length > 0) {
            params.colors = filters.colors.join(',');
        }

        // Add materials filter
        if (filters.materials && filters.materials.length > 0) {
            params.materials = filters.materials.join(',');
        }

        // Add height filter
        if (filters.height) {
            params.height = filters.height;
        }

        // Add price range filter
        if (filters.minPrice !== undefined && filters.minPrice !== null) {
            params.min_price = filters.minPrice;
        }
        if (filters.maxPrice !== undefined && filters.maxPrice !== null) {
            params.max_price = filters.maxPrice;
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
    },

    /**
     * Get product tags
     */
    async getTags(filters = {}) {
        try {
            const params = {
                per_page: 100,
                hide_empty: true
            };

            if (filters.category) {
                params.category = filters.category;
            }

            // Pass selected tags to calculate availability
            if (filters.tags && filters.tags.length > 0) {
                params.selected_tags = filters.tags.join(',');
            }

            // Pass colors to calculate availability
            if (filters.colors && filters.colors.length > 0) {
                params.colors = filters.colors.join(',');
            }

            // Pass materials to calculate availability
            if (filters.materials && filters.materials.length > 0) {
                params.materials = filters.materials.join(',');
            }

            // Pass height to calculate availability
            if (filters.height) {
                params.height = filters.height;
            }

            // Pass price range to calculate availability
            if (filters.minPrice !== undefined && filters.minPrice !== null) {
                params.min_price = filters.minPrice;
            }
            if (filters.maxPrice !== undefined && filters.maxPrice !== null) {
                params.max_price = filters.maxPrice;
            }

            const response = await axios.get('/wp-json/theme/v1/tags', { params });
            return response.data;
        } catch (error) {
            console.error('Error fetching tags:', error);
            throw new Error('Failed to load tags. Please try again.');
        }
    },

    /**
     * Get product colors
     */
    async getColors(filters = {}) {
        try {
            const params = {
                per_page: 100,
                hide_empty: true
            };

            if (filters.category) {
                params.category = filters.category;
            }

            // Pass selected tags to calculate availability
            if (filters.tags && filters.tags.length > 0) {
                params.selected_tags = filters.tags.join(',');
            }

            // Pass selected colors to calculate availability
            if (filters.colors && filters.colors.length > 0) {
                params.selected_colors = filters.colors.join(',');
            }

            // Pass selected materials to calculate availability
            if (filters.materials && filters.materials.length > 0) {
                params.selected_materials = filters.materials.join(',');
            }

            // Pass selected height to calculate availability
            if (filters.height) {
                params.selected_height = filters.height;
            }

            // Pass price range to calculate availability
            if (filters.minPrice !== undefined && filters.minPrice !== null) {
                params.min_price = filters.minPrice;
            }
            if (filters.maxPrice !== undefined && filters.maxPrice !== null) {
                params.max_price = filters.maxPrice;
            }

            const response = await axios.get('/wp-json/theme/v1/colors', { params });
            return response.data;
        } catch (error) {
            console.error('Error fetching colors:', error);
            throw new Error('Failed to load colors. Please try again.');
        }
    },

    /**
     * Get product materials
     */
    async getMaterials(filters = {}) {
        try {
            const params = {
                per_page: 100,
                hide_empty: true
            };

            if (filters.category) {
                params.category = filters.category;
            }

            // Pass selected tags to calculate availability
            if (filters.tags && filters.tags.length > 0) {
                params.selected_tags = filters.tags.join(',');
            }

            // Pass selected colors to calculate availability
            if (filters.colors && filters.colors.length > 0) {
                params.selected_colors = filters.colors.join(',');
            }

            // Pass selected materials to calculate availability
            if (filters.materials && filters.materials.length > 0) {
                params.selected_materials = filters.materials.join(',');
            }

            // Pass selected height to calculate availability
            if (filters.height) {
                params.selected_height = filters.height;
            }

            // Pass price range to calculate availability
            if (filters.minPrice !== undefined && filters.minPrice !== null) {
                params.min_price = filters.minPrice;
            }
            if (filters.maxPrice !== undefined && filters.maxPrice !== null) {
                params.max_price = filters.maxPrice;
            }

            const response = await axios.get('/wp-json/theme/v1/materials', { params });
            return response.data;
        } catch (error) {
            console.error('Error fetching materials:', error);
            throw new Error('Failed to load materials. Please try again.');
        }
    },

    /**
     * Get product heights
     */
    async getHeights(filters = {}) {
        try {
            const params = {
                per_page: 100,
                hide_empty: true
            };

            if (filters.category) {
                params.category = filters.category;
            }

            // Pass selected tags to calculate availability
            if (filters.tags && filters.tags.length > 0) {
                params.selected_tags = filters.tags.join(',');
            }

            // Pass selected colors to calculate availability
            if (filters.colors && filters.colors.length > 0) {
                params.selected_colors = filters.colors.join(',');
            }

            // Pass selected materials to calculate availability
            if (filters.materials && filters.materials.length > 0) {
                params.selected_materials = filters.materials.join(',');
            }

            // Pass selected height to calculate availability
            if (filters.height) {
                params.selected_height = filters.height;
            }

            // Pass price range to calculate availability
            if (filters.minPrice !== undefined && filters.minPrice !== null) {
                params.min_price = filters.minPrice;
            }
            if (filters.maxPrice !== undefined && filters.maxPrice !== null) {
                params.max_price = filters.maxPrice;
            }

            const response = await axios.get('/wp-json/theme/v1/heights', { params });
            return response.data;
        } catch (error) {
            console.error('Error fetching heights:', error);
            throw new Error('Failed to load heights. Please try again.');
        }
    },

    /**
     * Get price range (min and max)
     */
    async getPriceRange(filters = {}) {
        try {
            const params = {};

            if (filters.category) {
                params.category = filters.category;
            }

            // Pass selected tags to calculate filtered range
            if (filters.tags && filters.tags.length > 0) {
                params.selected_tags = filters.tags.join(',');
            }

            // Pass selected colors to calculate filtered range
            if (filters.colors && filters.colors.length > 0) {
                params.selected_colors = filters.colors.join(',');
            }

            // Pass selected materials to calculate filtered range
            if (filters.materials && filters.materials.length > 0) {
                params.selected_materials = filters.materials.join(',');
            }

            // Pass selected height to calculate filtered range
            if (filters.height) {
                params.selected_height = filters.height;
            }

            const response = await axios.get('/wp-json/theme/v1/price-range', { params });
            return response.data;
        } catch (error) {
            console.error('Error fetching price range:', error);
            throw new Error('Failed to load price range. Please try again.');
        }
    }
};

export default productsApi;
