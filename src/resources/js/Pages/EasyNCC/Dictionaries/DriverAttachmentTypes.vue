<template>
  <DictionaryIndex
    type="driver-attachment-types"
    title="Tipologie Allegati Driver"
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
