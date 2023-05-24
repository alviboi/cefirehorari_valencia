{{-- <div id="modal_avis" uk-modal>
    <escriuavis-component />
</div>

<div id="modal_missatge" uk-modal>
    <escriumsg-component />
</div>
 --}}
 <escriuavissetmana-component :show-avisdiasetmana="this.showavisdiasetmana"></escriuavissetmana-component>


<escriuincidencia-component :show-incidencia="this.showModalInc"></escriuincidencia-component>

 <escriuavis-component :show-modal="this.showModal"></escriuavis-component>

 <editaperfil-component :show-edita="this.showEdita"></editaperfil-component>

 <escriumsg-component :show-missatge="this.showMissatge"/></escriumsg-component>

 <permisllarg-component :show-Permisllarg="this.showPermisllarg"/></permisllarg-component>

 <calendariguardies-component :show-Calendariguardies="this.showCalendariguardies"/></calendariguardies-component>


 
