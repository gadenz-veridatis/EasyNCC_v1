<template>
  <DictionaryIndex
    type="leave-types"
    title="Tipologie Congedo"
    :has-is-active="true"
  />
</template>

<script>
import DictionaryIndex from "./Index.vue";

export default {
  components: {
    DictionaryIndex,
  },
};
</script>
