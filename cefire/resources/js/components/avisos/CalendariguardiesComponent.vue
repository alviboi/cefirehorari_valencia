<template>
  <div id="modal_calendariguardies" uk-modal>
    <div
      style="background-color: #f7f7f7"
      class="uk-modal-dialog uk-modal-body"
    >
      <fieldset class="uk-fieldset">
        <legend class="uk-legend">Calendari guàrdies vesprada</legend>
        <div class="uk-margin">
          <!-- Ací va el calendari de guàrdies vesprada -->
          <table class="uk-table uk-table-justify uk-table-divider">
            <thead>
              <tr>
                <th>DIA</th>
                <th>PERSONES GUÀRDIA</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(item, key, index) in setmana" :key="index">
                <!-- <td> -->
                <td>
                  <b>{{ key }}</b>
                </td>
                <div v-for="(item2, key2, index2) in users" :key="index2">
                  <div
                    v-if="item == item2['diaguardia']"
                    style="padding: 0px !important"
                  >
                    {{ item2["name"] }}
                  </div>
                </div>

                <!-- </td> -->
              </tr>
            </tbody>
          </table>
        </div>
      </fieldset>

      <div></div>
      <p class="uk-text-right">
        <transition-group name="list-complete2">
          <button
            :key="1 + id"
            @click.prevent="ix"
            class="uk-button uk-button-default"
            type="button"
            :disabled="avis"
          >
            Tanca
          </button>
        </transition-group>
      </p>
    </div>
  </div>
</template>

<script>
/**
Aquest component crea un modal per a avisar falta dia setmana guardia

 */

export default {
  data() {
    return {
      id: null,
      avis: false,
      diasetmana: "",
      setmana: {
        Dilluns: 1,
        Dimarts: 2,
        Dimecres: 3,
        Dijous: 4,
        Exempt: 11,
      },
      users: [],
    };
    // props: ['show-incidencia']
  },
  props: ["showCalendariguardies"],
  watch: {
    showCalendariguardies() {
      this.mostra_modal();
    },
  },
  methods: {
    // Botó ixir del modal. Envia event per a tancar-los
    ix() {
      this.$eventBus.$emit("tanca-calendariguardies");
    },
    agafa_users() {
      axios
        .get("user")
        .then((res) => {
          console.log(res);
          this.users = res.data;
        })
        .catch((err) => {
          console.error(err);
        });
    },
    // Mostra el modal en funció del que diga l'event
    mostra_modal() {
      if (this.showCalendariguardies == true) {
        UIkit.modal("#modal_calendariguardies", {
          bgClose: false,
          escClose: false,
          modal: false,
          keyboard: false,
        }).show();
      } else {
        UIkit.modal("#modal_calendariguardies").hide();
      }
    },
  },
  mounted() {
    this.id = this._uid;
    this.agafa_users();
    //this.mostra_modal2();
  },
};
</script>

<style>
</style>
