<template>
  <DictionaryIndex
    type="service-types"
    title="Tipologie di Servizio"
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
