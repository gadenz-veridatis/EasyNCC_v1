<template>
  <DictionaryIndex
    type="accounting-entries"
    title="Causali Contabili"
    :has-abbreviation="true"
    :has-description="true"
    :has-type="true"
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
