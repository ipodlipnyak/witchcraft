<template>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">


  
  <b-table striped hover :items="files" :fields="fields">
		<!-- A virtual column -->
		<template v-slot:cell(size)="data">
			{{ formatBytes(data.value) }}
		</template>
  
		<!-- A virtual column -->
		<template v-slot:cell(progress)="data">
			<b-progress :value="Number(data.value)" show-progress animated></b-progress>
		</template>
  </b-table>

  <file-upload
    ref="upload"
    v-model="files"
    post-action="/post.method"
    put-action="/put.method"
    @input-file="inputFile"
    @input-filter="inputFilter"
    
    chunk-enabled
    :chunk="chunk"

  >
  Upload file
  </file-upload>

  <button v-show="!$refs.upload || !$refs.upload.active" @click.prevent="$refs.upload.active = true" type="button">Start upload</button>
  <button v-show="$refs.upload && $refs.upload.active" @click.prevent="$refs.upload.active = false" type="button">Stop upload</button>

            </div>
        </div>
    </div>
</template>

<script>

import FileUpload from 'vue-upload-component';
import { TablePlugin, ProgressPlugin } from 'bootstrap-vue';

Vue.use(TablePlugin)
Vue.use(ProgressPlugin)

    export default {
		props: ['apiToken'],
	  	data: function () {
		    return {
		      files: [],
		      fields: ['name','type','size','success','progress'],
		    }
		},
		computed: {
			uploadAction: function () {
				return '/api/files/upload?api_token=' + this.apiToken;
			},
			
			chunk: function () {
		          return {
		        	  action: this.uploadAction,
			          minSize: 1048576,
			          maxActive: 3,
			          maxRetries: 5
		          };
			},
		},

		
		components: {
			FileUpload,
// 			TablePlugin
		},
		
        mounted() {
            console.log('Component mounted.')
        },
        
        methods: {
            /**
             * Has changed
             * @param  Object|undefined   newFile   Read only
             * @param  Object|undefined   oldFile   Read only
             * @return undefined
             */
            inputFile: function (newFile, oldFile) {
              if (newFile && oldFile && !newFile.active && oldFile.active) {
                // Get response data
                console.log('response', newFile.response)
                if (newFile.xhr) {
                  //  Get the response status code
                  console.log('status', newFile.xhr.status)
                }
              }
            },
            
            /**
             * Pretreatment
             * @param  Object|undefined   newFile   Read and write
             * @param  Object|undefined   oldFile   Read only
             * @param  Function           prevent   Prevent changing
             * @return undefined
             */
            inputFilter: function (newFile, oldFile, prevent) {
				if (newFile && !oldFile) {
                	// Filter non-image file
                	if (!/\.(mkv|mp4)$/i.test(newFile.name)) {
                  		return prevent()
                	}
            	}
				
			      // Create a blob field
			      newFile.blob = ''
			      let URL = window.URL || window.webkitURL
			      if (URL && URL.createObjectURL) {
			        newFile.blob = URL.createObjectURL(newFile.file)
			      }
				
        	},
        	
        	formatBytes: function(bytes, decimals = 2) {
        	    if (bytes === 0) return '0 Bytes';

        	    const k = 1024;
        	    const dm = decimals < 0 ? 0 : decimals;
        	    const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];

        	    const i = Math.floor(Math.log(bytes) / Math.log(k));

        	    return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
        	}
    	}
	}
</script>
