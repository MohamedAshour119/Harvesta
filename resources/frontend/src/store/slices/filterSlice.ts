import { createSlice, type PayloadAction } from '@reduxjs/toolkit';

export interface Category {
  id: number;
  name: string;
  slug: string;
  products_count: number;
  selected: boolean;
}

interface FilterState {
  selectedFilter: string | null;
  priceRange: [number, number];
  categories: Category[];
  appliedFilters: number;
  selectedCategorySlugs: string[];
}

const initialState: FilterState = {
  selectedFilter: null,
  priceRange: [0, 0], // 0 max means unlimited
  categories: [],
  appliedFilters: 0,
  selectedCategorySlugs: [],
};

export const filterSlice = createSlice({
  name: 'filter',
  initialState,
  reducers: {
    setSelectedFilter: (state, action: PayloadAction<string | null>) => {
      state.selectedFilter = action.payload;
    },
    setPriceRange: (state, action: PayloadAction<[number, number]>) => {
      state.priceRange = action.payload;
      updateAppliedFilters(state);
    },
    setCategories: (state, action: PayloadAction<Omit<Category, 'selected'>[]>) => {      
      state.categories = action.payload.map(cat => ({
        ...cat,
        // Preserve selection if categories are re-fetched while some are selected
        selected: state.selectedCategorySlugs.includes(cat.slug),
      }));
    },
    toggleCategory: (state, action: PayloadAction<string>) => { 
      const slugToToggle = action.payload;
      const categoryInState = state.categories.find(cat => cat.slug === slugToToggle);

      if (categoryInState) {
        const isCurrentlySelected = state.selectedCategorySlugs.includes(slugToToggle);

        if (isCurrentlySelected) {
          state.selectedCategorySlugs = state.selectedCategorySlugs.filter(s => s !== slugToToggle);
          categoryInState.selected = false;
        } else {
          state.selectedCategorySlugs.push(slugToToggle);
          categoryInState.selected = true;
        }
        state.selectedFilter = null;
      }
      updateAppliedFilters(state);
    },
    setSelectedCategorySlugs: (state, action: PayloadAction<string[]>) => {
      state.selectedCategorySlugs = action.payload;
      state.categories.forEach(cat => {
        cat.selected = action.payload.includes(cat.slug);
      });
      if (state.selectedCategorySlugs.length > 0) {
        state.selectedFilter = null;
      }
      updateAppliedFilters(state);
    },
    setSelectedSortFilter: (state, action: PayloadAction<string | null>) => {
      state.selectedFilter = action.payload; // e.g., 'most-popular', 'newest'

      if (action.payload !== null) {
        // Clear category selections when a primary sort filter is chosen
        state.selectedCategorySlugs = [];
        state.categories.forEach(cat => {
          cat.selected = false;
        });
      }
      updateAppliedFilters(state); // Recalculate applied filters
    },
    clearFilters: (state) => {
      state.categories = state.categories.map(cat => ({...cat, selected: false}));
      state.priceRange = [0, 0]; // reset to unlimited
      state.selectedCategorySlugs = [];
      state.selectedFilter = null;
      state.appliedFilters = 0;
    },
  },
});

const updateAppliedFilters = (state: FilterState) => {
  let count = 0;
  state.categories.forEach(cat => { if (cat.selected) count++; });
  // count price filter only if an upper bound is applied
  if (state.priceRange[1] > 0) count++;
  state.appliedFilters = count;
};

export const { 
  setSelectedFilter, 
  setPriceRange, 
  setCategories, 
  toggleCategory, 
  setSelectedCategorySlugs,
  setSelectedSortFilter, 
  clearFilters 
} = filterSlice.actions;

export default filterSlice.reducer;
