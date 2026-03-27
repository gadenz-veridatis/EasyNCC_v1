<template>
  <DictionaryIndex
    type="dress-codes"
    title="Dress Codes"
    :has-description="true"
    :has-is-default="true"
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
