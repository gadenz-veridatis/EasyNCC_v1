<template>
  <DictionaryIndex
    type="service-statuses"
    title="Stati Servizi"
    :has-description="true"
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
