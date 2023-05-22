<template>
  <div>
    <div class="uk-align-center">
      <h2 class="uk-align-center">Llistat Compensacions per validar</h2>
    </div>

    <div>
      <transition-group name="list3" tag="div">
        <div v-for="item in compensacions" :key="item.id">
          <div class="llistatcomp">
            <div class="data">
              <span data-uk-icon="icon: calendar"></span>
              {{ item.data }}
            </div>
            <div class="nom">
              <span data-uk-icon="icon: user"></span>
              <b>{{ item.name }}</b>
            </div>
            <div class="mati">
              <span data-uk-icon="icon: clock"></span>
              <span v-if="item.inici == '09:00:00'"
                ><i class="fas fa-sun"></i>{{ item.inici }} -
                {{ item.fi }}</span
              >
              <span v-else
                ><i class="fas fa-moon"></i>{{ item.inici }} -
                {{ item.fi }}</span
              >
            </div>
            <div class="motiu">
              <span data-uk-icon="icon: comments"></span>
              {{ item.motiu }}
            </div>
            <div class="botons">
              <div
                @click.prevent="valida(item.id)"
                class="uk-icon-button uk-text-success"
                uk-icon="check"
              ></div>
              <div
                @click.prevent="borra(item.id)"
                class="uk-icon-button uk-text-danger"
                uk-icon="close"
              ></div>
            </div>
          </div>
        </div>
      </transition-group>
      <!-- FITXATGES OBLIDATS -->
      <hr />
      <h1 class="uk-heading-line uk-text-center">
        <div class="uk-inline">
          FITXATGES OBLIDATS
          <button
            :uk-toggle="'target: #cefire_afg-modal'"
            class="uk-icon-button uk-button-primary"
            uk-icon="icon: plus"
          ></button>
        </div>
      </h1>
      <transition-group name="list3" tag="div">
        <div
          v-for="item in oblits"
          :key="item.id"
          tabindex="-2"
          style="z-index: -2"
        >
          <div class="llistatcomp">
            <div class="data">
              <span data-uk-icon="icon: calendar"></span>
              {{ item.data }}
            </div>
            <div class="nom">
              <span data-uk-icon="icon: user"></span>
              <b>{{ item.name }}</b>
            </div>
            <div class="mati">
              <span data-uk-icon="icon: calendar"></span>
              <b>{{ item.data }}</b>
            </div>

            <div class="motiu">
              <span data-uk-icon="icon: time"></span>
              <b>{{ item.inici }}</b>
              <span>a</span>
              <vue-timepicker
                append-to-body
                :minute-interval="1"
                v-model="item.fi"
              ></vue-timepicker>
            </div>

            <div class="botons">
              <div
                @click.prevent="valida_oblidat(item.id, item.fi)"
                class="uk-icon-button uk-text-success"
                uk-icon="check"
              ></div>
              <div
                @click.prevent="borra_oblidat(item.id)"
                class="uk-icon-button uk-text-danger"
                uk-icon="close"
              ></div>
            </div>
          </div>
        </div>
      </transition-group>

      <!-- ###################################### -->
    </div>
    <!-- Fitxatge modal -->
    <div :id="'cefire_afg-modal'" uk-modal>
      <div class="uk-modal-dialog uk-modal-body">
        <fieldset class="uk-fieldset">
          <div class="uk-margin">
            <div class="uk-text-medium">
              Aquest fitxatge és sols en cas que se li haja oblidat tant
              l'entrada com la sortida
            </div>
            <div class="uk-margin">
              <form
                class="uk-width-expand uk-search uk-search-default"
                autocomplete="on"
              >
                <div class="uk-margin">
                  <label for="seleccio">Selecciona assessor</label>
                  <select
                    id="seleccio"
                    v-model="busca_ass"
                    class="uk-select"
                    aria-label="Select"
                  >
                    <option
                      v-for="(user, key) in users"
                      :key="key"
                      :value="user.id"
                    >
                      {{ user.name }}
                    </option>
                  </select>
                </div>
              </form>
            </div>
            <div class="uk-child-width-expand@s" uk-grid>
              <div>
                <label>Dia fitxatge: </label>
                <Datepicker
                  :language="ca"
                  :monday-first="true"
                  v-model="data"
                  placeholder="Selecciona dia:"
                  input-class="uk-input uk-inline"
                >
                </Datepicker>
              </div>
              <div>
                <label>Hora entrada: </label>
                <vue-timepicker
                  :hour-range="[[7, 19]]"
                  :minute-interval="5"
                  v-model="inici"
                ></vue-timepicker>
              </div>
              <div>
                <span> Hora sortida: </span>
                <vue-timepicker
                  :hour-range="[[7, 19]]"
                  :minute-interval="5"
                  v-model="fi"
                ></vue-timepicker>
              </div>
            </div>
          </div>
        </fieldset>
        <p class="uk-text-right">
          <button
            class="uk-button uk-button-default uk-modal-close"
            type="button"
          >
            Cancel·la
          </button>
          <button
            @click.prevent="afegix_cefire"
            class="uk-button uk-button-primary"
            type="button"
          >
            Fitxa
          </button>
        </p>
      </div>
    </div>
  </div>
</template>

<script>
/**
 * En aquest component es mostren tots els justificants de tots els permisos dels assessors, es pot buscar per assessor i per dades concretes
 */
import Datepicker from "vuejs-datepicker";
import { ca } from "vuejs-datepicker/dist/locale";
import VueTimepicker from "vue2-timepicker";
import "vue2-timepicker/dist/VueTimepicker.css";
export default {
  data() {
    return {
      hui: new Date(),
      compensacions: {},
      oblits: {},
      inici: "",
      fi: "",
      users: [],
      busca_ass: "",
      data: null,
      ca: ca,
    };
  },
  components: {
    VueTimepicker,
    Datepicker,
  },
  methods: {
    agafa_oblits() {
      let url = "usuaris_oblit_fitxatge";
      axios
        .get(url)
        .then((res) => {
          this.oblits = res.data;
          console.log(res);
        })
        .catch((err) => {
          this.$toast.error(err.response.data.message);
        });
    },
    agafa_compensacions() {
      let url = "compensacions_no_validades";
      axios
        .post(url)
        .then((res) => {
          this.compensacions = res.data;
          console.log(res);
        })
        .catch((err) => {
          this.$toast.error(err.response.data.message);
        });
    },
    borra(id) {
      let url = "compensa/" + id;
      for (let index = 0; index < this.compensacions.length; index++) {
        if (this.compensacions[index].id == id) {
          this.compensacions.splice(index, 1);
        }
      }
      axios
        .delete(url)
        .then((res) => {
          console.log(res);
          this.$toast.success("Borrat correctament");
        })
        .catch((err) => {
          console.error(err);
          this.$toast.error(err.response.data.message);
        });
    },
    // Edita assessor
    valida(id) {
      let url = "validacompensacio";
      for (let index = 0; index < this.compensacions.length; index++) {
        if (this.compensacions[index].id == id) {
          this.compensacions.splice(index, 1);
        }
      }
      let params = {
        id: id,
      };
      axios
        .post(url, params)
        .then((res) => {
          console.log(res);
          this.$toast.success("Has validat la compensació");
        })
        .catch((err) => {
          this.$toast.error(err.response.data.message);
        });
    },
    afegix_cefire() {
      //alert(this.data);
      if (
        this.data === null ||
        this.inici == "" ||
        this.fi == "" ||
        this.busca_ass == ""
      ) {
        this.$toast.error("Cal que emplenes totes les dades");
        return 0;
      }
      var params = {
        id: this.busca_ass,
        data: data_db(this.data),
        inici: this.inici,
        fi: this.fi,
      };
      axios
        .post("cefire_fitxa_oblit", params)
        .then((res) => {
          this.$toast.success(res.data);
        })
        .catch((err) => {
          this.$toast.error(err.response.data.message);
        });
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
  },
  mounted() {
    this.agafa_compensacions();
    this.agafa_oblits();    
    this.agafa_users();


  },
  watch: {},
};
</script>

<style lang="sass" scope>
$fondo: #EEC49A
.llistatcomp
  display: grid
  grid-template-columns: 0.7fr 1.1fr 0.8fr 2fr 0.3fr
  grid-template-rows: 1fr
  gap: 0px 0px
  grid-template-areas: "data nom mati motiu botons"
  border: 2px solid black
  border-radius: 10px
  margin: 10px
  padding: 10px
  box-shadow: 3px 6px 121px -42px rgba(0,0,0,0.75)
  align-content: center
  align-items: center
  background-color: $fondo
  comu
  overflow: hidden
  .data
    @extend comu
    grid-area: data
    .nom
      @extend comu
      grid-area: nom
    .mati
      @extend comu
      grid-area: mati
    .motiu
      grid-area: motiu
    .botons
      @extend comu
      grid-area: botons
      display: flex
      align-items: right
      text-align: right
</style>
