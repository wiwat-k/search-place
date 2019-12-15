<template>
  <b-container fluid>
    <b-row>
      <b-col cols="12" sm="3" class="col-main border-1">
        <div class="box-search">
          <b-input-group>
            <b-form-input v-model="textSearch" placeholder="Search Restaurant"></b-form-input>
            <b-input-group-append>
              <b-button variant="outline-primary" v-if="!btn_loading" @click="search('')">
                <font-awesome-icon icon="search" />Search
              </b-button>
              <b-button v-else variant="primary" disabled>
                <b-spinner small type="grow"></b-spinner>Loading...
              </b-button>
            </b-input-group-append>
          </b-input-group>
        </div>
        <div class="box-list">
          <b-list-group v-if="itemRestaurant.length > 0">
            <b-list-group-item
              v-for="(item,i) in itemRestaurant"
              :key="i"
              @click="marker(item.geometry.location.lat,item.geometry.location.lng)"
              href="#"
            >
              <b-row no-gutters>
                <b-col md="4" v-if="item.photos">
                  <b-card-img
                    :src="'https://maps.googleapis.com/maps/api/place/photo?maxwidth=200&photoreference='+item.photos[0].photo_reference+'&key='+gkey"
                    class="rounded-0"
                  ></b-card-img>
                </b-col>
                <b-col :md="`${(item.photos?'8':'12')}`" style="padding:5px;">
                  <p>{{item.name}}</p>
                  <p>{{item.formatted_address}}</p>
                  <p>Open Now : 
                    {{
                    marker.open_now == true ? 'Open' : 'Close'
                    }}
                  </p>
                </b-col>
              </b-row>
            </b-list-group-item>
            <b-list-group-item>
              <b-button
                :disabled="btn_loading"
                variant="outline-primary"
                @click="search(tokenPage)"
              >
                Next Page
                <font-awesome-icon style="margin-left:10px;" icon="angle-right" />
              </b-button>
            </b-list-group-item>
          </b-list-group>
          <p style="margin:15px;" v-else>No Result</p>
        </div>
      </b-col>
      <b-col cols="12" sm="9" class="col-main div-map border-2">
        <GmapMap :center="{lat: lat, lng: lng}" :zoom="zoom" style="width: 100%; height: 100%">
          <GmapMarker
            v-for="(item,i) in itemRestaurant"
            :key="i"
            ref="myMarker"
            :position="google && new google.maps.LatLng(item.geometry.location.lat,item.geometry.location.lng)"
            @click="toggleInfoWindow(item,i)"
          >></GmapMarker>
          <gmap-info-window
            :options="infoOptions"
            :position="infoWindowPos"
            :opened="infoWinOpen"
            @closeclick="infoWinOpen=false"
          >
            <div v-html="infoContent"></div>
          </gmap-info-window>
        </GmapMap>
      </b-col>
    </b-row>
  </b-container>
</template>

<script>
import { gmapApi } from 'vue2-google-maps'
export default {
  computed: {
    google: gmapApi
  },
  data: () => {
    return {
      btn_loading: false,
      textSearch: 'Bangsue',
      itemRestaurant: [],
      tokenPage: '',
      lat: 13.8150564,
      lng: 100.5289701,
      zoom: 15,
      infoContent: '',
      infoWindowPos: {
        lat: 0,
        lng: 0
      },
      infoWinOpen: false,
      currentMidx: null,
      //optional: offset infowindow so it visually sits nicely on top of our marker
      infoOptions: {
        pixelOffset: {
          width: 0,
          height: -35
        }
      }
    }
  },
  methods: {
    marker(val1, val2) {
      this.lat = val1
      this.lng = val2
      this.zoom = 20
    },
    async search(tokenPage) {
      this.btn_loading = true
      await this.axios
        .get('http://localhost:8000/api/restaurant/search', {
          params: {
            text: this.textSearch,
            gkey: this.gkey,
            pagetoken: tokenPage
          }
        })
        .then(response => {
          if (response.status == 'ZERO_RESULTS') {
            this.itemRestaurant = []
            this.tokenPage = ''
          } else {
            var results = response.data.results
            this.itemRestaurant = results
            this.tokenPage = response.data.next_page_token
            this.lat = results[0].geometry.location.lat
            this.lng = results[0].geometry.location.lng
          }
        })
        .catch(error => {
          // handle error
          console.log(error)
        })
        .finally(() => {
          this.btn_loading = false
        })
    },
    toggleInfoWindow: function(marker, idx) {
      this.infoWindowPos = marker.geometry.location
      this.infoContent = this.getInfoWindowContent(marker)

      //check if its the same marker that was selected if yes toggle
      if (this.currentMidx == idx) {
        this.infoWinOpen = !this.infoWinOpen
      }
      //if different marker set infowindow to open and reset current marker index
      else {
        this.infoWinOpen = true
        this.currentMidx = idx
      }
    },
    getInfoWindowContent: function(marker) {
      // create info window content
      var image = ''
      if (marker.photos !== undefined) {
        image = `<img src="https://maps.googleapis.com/maps/api/place/photo?maxwidth=200&photoreference=${marker.photos[0].photo_reference}&key=${this.gkey}" alt="Placeholder image">`
      }
      return `<div class="card" style="max-width:200.5px;">
  <div class="card-image">
    <figure class="image is-4by3">
      ${image}
    </figure>
  </div>
  <div class="card-content">
    <div class="content" style="padding:5px;">
      <p class="title is-4">${marker.name}</p>
      <p class="title is-4">${marker.formatted_address}</p>
      <p class="title is-4">Open Now : ${
        marker.open_now == true ? 'Open' : 'Close'
      }</p>
    </div>
  </div>
</div>`
    }
  },
  mounted() {
    this.search()
  }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>
.border-1 {
  border-right: 1px solid rgba(0, 0, 0, 0.1);
}
.border-1 {
  border-right: 1px solid rgba(0, 0, 0, 0.1);
}
.box-search {
  position: relative;
  padding: 15px;
  margin-right: -15px;
  margin-left: -15px;
  border-bottom: 1px solid rgba(0, 0, 0, 0.05);
  box-shadow: 0px 1px 5px -1px;
  z-index: 10;
}
.div-map {
  padding: 0px;
}
.spinner-grow-sm {
  margin: 5px !important;
  width: 0.7rem !important;
  height: 0.7rem !important;
}
.list-group-item:first-child,
.list-group-item:last-child {
  border-radius: 0px;
}
.list-group-item {
  border-right: 0px;
}

.box-list {
  padding: 0px;
  margin-right: -15px;
  margin-left: -15px;
  overflow-y: auto;
}
@media screen and (max-width: 480px) {
  .box-list {
    height: calc(50vh - 69px);
  }
  .div-map {
    padding: 0px;
    height: 50vh;
  }
  .col-main {
    height: 50vh;
  }
}
@media screen and (min-width: 481px) {
  .box-list {
    height: calc(100vh - 69px);
  }
  .col-main {
    height: 100vh;
  }
}
</style>
