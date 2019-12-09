<template>
<div class="container"><div class="row justify-content-center"><div class="col-md-8">
            
  <b-button v-if="filesUploaded.length > 0" block squared v-b-toggle.uploaded-files variant="primary">Uploaded files</b-button>
  <b-collapse v-if="filesUploaded.length > 0" id="uploaded-files" class="mt-2">

  <b-table striped hover 
  		:items="filesUploaded" 
  		:fields="fields_uploaded"
		:sort-by.sync="uploaded_sortBy"
      	:sort-desc.sync="uploaded_sortDesc">
      	
		<template v-slot:cell(thumb)="data">
			<a :href="'/storage/media/' + data.item.id">
				<b-img height="75" :src="'/storage/thumbs/' + data.item.id"></b-img>
			</a>
		</template>

		<template v-slot:cell(upload_session.size)="data">
			<p v-if="data.item.upload_session">
				{{ formatBytes(data.item.upload_session.size) }}
			</p>
		</template>
		
		<template v-slot:cell(delete)="data">
			<b-button @click="deleteFile(data.item.id)" block variant="danger">
			<font-awesome-icon icon="times" :style="{ color: 'white' }" size="sm"/>
			</b-button>
		</template>
  </b-table>

  </b-collapse>

<!-- 
<hr v-if="filesUploaded.length > 0"/>
 -->

  <b-table id="files-to-upload" v-if="files.length > 0" striped hover :items="files" :fields="fields_files">
		<template v-slot:cell(size)="data">
			{{ formatBytes(data.value) }}
		</template>
  
		<template v-slot:cell(progress)="data">
			<font-awesome-icon v-if="data.item.success" icon="check" :style="{ color: 'green' }" size="lg"/>
			<b-button v-else-if="data.item.progress == '0.00'" @click="removeFileFromUploadQuery(data.item)" block variant="danger">
			<font-awesome-icon icon="times" :style="{ color: 'white' }" size="sm"/>
			</b-button>
			<b-progress v-else :value="Number(data.value)" show-progress animated></b-progress>
		</template>
  </b-table>

    <b-button-group size="bg" class="btn-block">
    
  <file-upload
    ref="upload"
    v-model="files"
    :post-action="uploadAction"
    @input-file="inputFile"
    @input-filter="inputFilter"
    
    chunk-enabled
    :chunk="chunk"
    
    :multiple="true"
    
    
    class="btn btn-primary btn-square"

  >
  <font-awesome-icon icon="plus" :style="{ color: 'white' }"/>
  Upload file
  </file-upload>
    
      <b-button squared v-if="files.length > 0" v-show="!$refs.upload || !$refs.upload.active" @click.prevent="$refs.upload.active = true" variant="success">Start upload</b-button>
      <b-button squared v-if="files.length > 0" v-show="$refs.upload && $refs.upload.active" @click.prevent="$refs.upload.active = false" variant="danger">Stop upload</b-button>
    </b-button-group>

            
        
</div></div></div>
</template>

<script>

jQuery.expr.filters.offscreen = function(el) {
	  var rect = el.getBoundingClientRect();
	  return (
	           (rect.x + rect.width) < 0 
	             || (rect.y + rect.height) < 0
	             || (rect.x > window.innerWidth || rect.y > window.innerHeight)
	         );
	};
	
window.checkOverflow = function (el)
	{
	   var curOverflow = el.style.overflow;

	   if ( !curOverflow || curOverflow === "visible" )
	      el.style.overflow = "hidden";

	   var isOverflowing = el.clientWidth < el.scrollWidth 
	      || el.clientHeight < el.scrollHeight;

	   el.style.overflow = curOverflow;

	   return isOverflowing;
	}

import FileUpload from 'vue-upload-component';
import { ImagePlugin, TablePlugin, ProgressPlugin, CollapsePlugin, ButtonPlugin, ButtonGroupPlugin } from 'bootstrap-vue';

import { library } from '@fortawesome/fontawesome-svg-core'
import { fas } from '@fortawesome/free-solid-svg-icons'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'

import {TweenMax} from "gsap/TweenMax";

library.add(fas)

Vue.use(ImagePlugin)
Vue.use(TablePlugin)
Vue.use(ProgressPlugin)
Vue.use(CollapsePlugin)
Vue.use(ButtonPlugin)
Vue.use(ButtonGroupPlugin)



    export default {
		props: ['apiToken'],
	  	data: function () {
		    return {
		    	slides: [
		    		'uploader',
		    		'project'
		    	],
			  
		      files: [],

		      fields_files: [
		    	  'name',
// 		    	  'type',
		    	  'size',
		    	  'progress'],
		      
		      filesUploaded: [],
		      fields_uploaded: [
		    	  { key: 'thumb', label: '', sortable: false },
		    	  { key: 'label', label: 'Name', sortable: true },
// 		          { key: 'upload_session.mime_type', label: 'Type', sortable: true },
		          { key: 'upload_session.size', label: 'Size', sortable: true },
		          { key: 'delete', label: '', sortable: false },
		          ],
		      uploaded_sortBy: 'label',
		      uploaded_sortDesc: false,
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
			
			uploadFinished: function () {
				let result = true;
				
				this.files.forEach(function(file){
					if (file.success == false) {
						result = false;
					}
				});
				
				return result;
			},
		},

		
		components: {
			FileUpload,
			FontAwesomeIcon,
		},
		
		created() {
			window.addEventListener("scroll", this.handleScroll)
		},
		
        mounted() {
			this.getFiles();
        },
        
        destroyed () {
        	window.removeEventListener("scroll", this.handleScroll)
        },
        
        watch: {
            uploadFinished: function (newVal, oldVal) {
            	if (oldVal == false) {
            		this.getFiles();
            		this.files = [];
            	}
			}
		},
        
        methods: {
        	deleteFile: function(id) {
        		self = this;
        		axios.delete('/api/files/' + id + '?api_token=' + this.apiToken)
        		  .then(function (response) {
        		    if (response.data.status == 'success') {
        		    	self.getFiles();
        		    } else {
        		    	console.log(response.data);
        		    }
        		  })
        		  .catch(function (error) {
        		    console.log(error);
        		  });
        	},
        	
        	removeFileFromUploadQuery: function(file) {
        		let index = this.files.indexOf(file);
        		this.files.splice(index, 1);
        	},
        	
        	getFiles: function() {
        		self = this;
        		axios.get('/api/files?api_token=' + this.apiToken)
        		  .then(function (response) {
        			  if (response.data.status == 'success') {
        				  self.filesUploaded = response.data.files;
        			  }
        		  })
        		  .catch(function (error) {
        		    console.log(error);
        		  });
        	},
        	
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
                	// Filter non-media file
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

<style scoped>
#uploaded-files, #files-to-upload {
	background: white;
}
</style>
