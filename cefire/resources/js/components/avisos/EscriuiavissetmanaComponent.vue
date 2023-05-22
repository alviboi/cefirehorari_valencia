<template>
  <div id="modal_diasetmana" uk-modal>
    <div
      style="background-color: #f7f7f7"
      class="uk-modal-dialog uk-modal-body"
    >
      <fieldset class="uk-fieldset">
        <div v-if="avis" class="uk-alert-danger" uk-alert>
          <a class="uk-alert-close" uk-close></a>
          <p>
            No tens cap dia de la setmana escollit com a guàrdia. Has d'escollir-ne un, sempre el podràs canviar anant al menú d'usuari (baix del teu nom)
          </p>
        </div>
        <legend class="uk-legend">
          Escollix el dia de la setmana de guardia. És el dia que et quedaràs
          per les vesprades.
        </legend>
        <div class="uk-margin">
          <label for="seleccio">Selecciona dia</label>
          <select
            id="seleccio"
            v-model="diasetmana"
            class="uk-select"
            aria-label="Select"
          >
            <option v-for="(dia, key) in setmana" :key="key" :value="dia" >
              {{ key }}
            </option>
          </select>
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
            Cancel·la
          </button>

          <button
            :key="2 + id"
            @click.prevent="envia"
            class="uk-button uk-button-primary"
            type="button"
          >
            Selecciona
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
        dilluns: 1,
        dimarts: 2,
        dimecres: 3,
        dijous: 4,
        Exempt: 11
      },
    };
    // props: ['show-incidencia']
  },
  props: ["showAvisdiasetmana"],
  watch: {
    showAvisdiasetmana() {
      this.mostra_modal();
    },
  },
  methods: {
    // Botó ixir del modal. Envia event per a tancar-los
    ix() {
      this.$eventBus.$emit("tanca-avisdiasetmana");
    },
    // Envia la informació emplenada
    envia() {
      if ((this.diasetmana == "")) {
        this.$toast.error("Falta algun paràmetre");
      } else {
        let url = "avisdiasetmana";
        let params = {
          'diasetmana': this.diasetmana,
        };
        axios
          .post(url, params)
          .then((res) => {
            console.log(res);
            this.$toast.success(res.data);
            this.avis = false;
          })
          .catch((err) => {
            console.error(err);
          });
      }
    },
    get_dia_setmana() {
      let url = "dia_setmana";
      axios
        .get(url)
        .then((res) => {
          console.log(res);
          this.dia_setmana = res.data["diasetmana"];
          document.getElementById("seleccio").value = this.dia_setmana;
          if (res.data["diasetmana"] == 10) {
            this.avis = true;
          }
        })
        .catch((err) => {
          console.error(err);
        });
    },
    // Mostra el modal en funció del que diga l'event
    mostra_modal() {
      if (this.showAvisdiasetmana == true) {
        UIkit.modal("#modal_diasetmana", {
          bgClose: false,
          escClose: false,
          modal: false,
          keyboard: false,
        }).show();
      } else {
        UIkit.modal("#modal_diasetmana").hide();
      }
    },
  },
  mounted() {
    this.id = this._uid;
    this.get_dia_setmana();
    //this.mostra_modal2();
  },
};
</script>

<style>
</style>
