<template>
  <div id="permis_llarg" uk-modal>
    <div
      style="background-color: #f7f7f7"
      class="uk-modal-dialog uk-modal-body"
    >
      <fieldset class="uk-fieldset">
        <legend class="uk-legend">Escollix el permis llarg</legend>
        <!-- <div class="uk-margin">
                        <input v-model="moscoso" class="uk-input" type="text" placeholder="Motiu de la incidència">
                    </div> -->
        <hr />
        <div class="uk-margin">
          <input
            v-model="permis_i"
            class="uk-form-large data-uk-input uk-width-1-1"
            type="text"
            placeholder="Motiu del permís"
          />
        </div>
        <div class="uk-child-width-1-2@s" uk-grid>
          <div>
            Des de:
            <Datepicker
              :language="ca"
              :monday-first="true"
              v-model="dia_perm1"
              placeholder="Des de"
              input-class="uk-input"
            ></Datepicker>
          </div>

          <div>
            Fins a:
            <Datepicker
              :language="ca"
              :monday-first="true"
              v-model="dia_perm2"
              placeholder="fins a"
              input-class="uk-input"
            >
            </Datepicker>
          </div>
        </div>

        <!-- <div class="uk-margin">                        
                        <label>Hora: </label>
                            <vue-timepicker :minute-interval="15" v-model="inici"></vue-timepicker>
                        <span> a </span>
                            <vue-timepicker :minute-interval="15" v-model="fi"></vue-timepicker> 
                        </div> -->

        <div
          :id="'upload' + this._uid"
          class="js-upload uk-placeholder uk-text-center"
        >
          <div style="color: green;">
            {{avis_pujada}}
          </div>
          <span uk-icon="icon: cloud-upload"></span>
          <span class="uk-text-middle"
            >Puja o arrastra l'arxiu justificant en <b>PDF</b></span
          >
          <div uk-form-custom>
            <input type="file" />
            <span class="uk-link">Selecciona un arxiu</span>
          </div>
        </div>
        <progress
          id="js-progressbar"
          class="uk-progress"
          value="0"
          max="100"
          hidden
        ></progress>
      </fieldset>
      <transition name="fade">
        <div v-if="resposta">
          {{ resposta }}
        </div>
      </transition>

      <div></div>
      <p class="uk-text-right">
        <transition-group name="list-complete2">
          <button
            :key="1 + id"
            @click.prevent="ix"
            class="uk-button uk-button-default"
            type="button"
          >
            Cancel·la
          </button>
          <button
            :key="2 + id"
            @click.prevent="envia"
            class="uk-button uk-button-primary"
            type="button"
          >
            Envia
          </button>
        </transition-group>
      </p>
    </div>
  </div>
</template>

<script>
/**
Aques component crea un modal per a escriure l'avís

 */

import Datepicker from "vuejs-datepicker";
import { ca } from "vuejs-datepicker/dist/locale";
import VueTimepicker from "vue2-timepicker";
import "vue2-timepicker/dist/VueTimepicker.css";
export default {
  data() {
    return {
      id: null,
      resposta: "",
      ca: ca,
      dia_perm1: new Date(),
      dia_perm2: new Date(),
      avis: "",
      permis_i: "",
      arxiu_pujat: "",
      avis_pujada: ""
    };
    // props: ['show-moscoso']
  },
  props: ["showPermisllarg"],
  watch: {
    showPermisllarg() {
      this.mostra_modal();
    },
  },
  components: {
    VueTimepicker,
    Datepicker,
  },
  methods: {
    // Botó ixir del modal. Envia event per a tancar-los
    ix() {
      this.$eventBus.$emit("tanca-permisllarg");
      this.resposta = "";
      this.avis_pujada = "";
      this.arxiu_pujat = "";
      this.permis_i = "";
    },
    // Envia la informació emplenada
    envia() {
      if (this.arxiu_pujat == "" || this.permis_i == "") {
        this.$toast.error("Cal pujar un justificant per a un permís llarg i escriure el motiu");
        return 0;
      }
      let url = "permisllarg";
      var params = {
        dia_inici: this.dia_perm1,
        dia_fi: this.dia_perm2,
        motiu: this.permis_i,
        arxiu: this.arxiu_pujat,
      };
      axios
        .post(url, params)
        .then((res) => {
          console.log(res);
          this.$toast.success("Permis registrat");
          this.$eventBus.$emit("permisllarg-enviat");
          //this.vacances="";
          this.dia_vac1 = "";
          this.dia_vac2 = "";
        })
        .catch((err) => {
          this.$toast.error(err.response.data.message);
          console.error(err);
        });
    },
    // Mostra el modal en funció del que diga l'event
    mostra_modal() {
      if (this.showPermisllarg == true) {
        UIkit.modal("#permis_llarg", {
          bgClose: false,
          escClose: false,
          modal: false,
          keyboard: false,
        }).show();
      } else {
        UIkit.modal("#permis_llarg").hide();
      }
    },
    mostra_modal() {
      self = this;
      var arxiu_p;
      this.id = this._uid;
      var bar = document.getElementById("js-progressbar");
      UIkit.upload("#upload" + this.id, {
        url: "upload_permis",
        multiple: false,
        name: "arxiu",
        beforeSend: function (e) {
          console.log("beforeSend", arguments);
          e.headers = {
            "X-CSRF-TOKEN": document
              .querySelector('meta[name="csrf-token"]')
              .getAttribute("content"),
          };
        },
        loadStart: function (e) {
          bar.removeAttribute("hidden");
          bar.max = e.total;
          bar.value = e.loaded;
        },

        progress: function (e) {
          bar.max = e.total;
          bar.value = e.loaded;
        },

        loadEnd: function (e) {
          bar.max = e.total;
          bar.value = e.loaded;
        },
        completeAll: function () {
          arxiu_p = arguments[0].response;
          console.log("completeAll", arguments[0].response);
          console.log(self.id);
          self.guarda_arxiu_pujat(arxiu_p);
          setTimeout(function () {
            bar.setAttribute("hidden", "hidden");
          }, 1000);
          self.avis_pujada = "Pujada realitzada correctament";
        },
      });
      if (this.showPermisllarg == true) {
        UIkit.modal("#permis_llarg", {
          bgClose: false,
          escClose: false,
          modal: false,
          keyboard: false,
        }).show();
      } else {
        UIkit.modal("#permis_llarg").hide();
      }
    },
    guarda_arxiu_pujat(a) {
      this.arxiu_pujat = a;
    },
  },
  mounted() {
    this.id = this._uid;
  },
};
</script>

<style>
</style>
