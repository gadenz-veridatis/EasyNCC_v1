import Vuex from 'vuex';

import layout from './modules/layout';
import todo from './modules/todo';

const store = new Vuex.Store({
  modules: {
    layout: layout, // Register the layout module
    todo

    // Add more modules as needed
  },
});

export default store;

