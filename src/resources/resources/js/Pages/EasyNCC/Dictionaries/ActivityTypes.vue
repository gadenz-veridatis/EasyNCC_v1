<template>
  <DictionaryIndex
    type="activity-types"
    title="Tipologie di Esperienze"
    :has-abbreviation="true"
    :has-description="true"
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
