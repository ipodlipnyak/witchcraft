<template>
<div class="container"><div class="row justify-content-center"><div class="col-md-8">

	<b-button-group size="bg" class="btn-block">
  		<file-upload
  			v-if="quotaLeft > 0"
    		ref="upload"
    		v-model="files"
    		:post-action="uploadAction"
    		@input-file="inputFile"
    		@input-filter="inputFilter"
    		chunk-enabled
    		:chunk="chunk"
    		:multiple="true"
    		class="btn btn-primary btn-square">
  		
  			<font-awesome-icon icon="plus" :style="{ color: 'white' }"/>
  			Upload file
		</file-upload>
    
		<b-button 
			squared 
			v-if="files.length > 0 && totalFilesSizeToUpload <= quotaLeft"
			v-show="!$refs.upload || !$refs.upload.active" 
			@click.prevent="$refs.upload.active = true" 
			variant="success">
			Start upload
		</b-button>
		
		<b-button 
			squared 
			v-if="files.length > 0" 
			v-show="$refs.upload && $refs.upload.active" 
			@click.prevent="$refs.upload.active = false" 
			variant="danger">
			Stop upload
		</b-button>
	</b-button-group>
	
	<b-table id="files-to-upload" v-if="files.length > 0" class="upload-tbl" :items="files" :fields="fields_files">
	
		<template v-slot:top-row="data">
          		<b-td colspan="4">
          			<b-progress :max="quotaLeft">
      					<b-progress-bar :style="quotaProgressToUploadCssStyle" :value="totalFilesSizeToUpload" animated show-progress>{{ formatBytes(totalFilesSizeToUpload) }}</b-progress-bar>
      					<b-progress-bar variant="info" :value="quotatoUploadLeft" striped show-progress>{{ formatBytes(quotatoUploadLeft) }}</b-progress-bar>
    				</b-progress>
          		</b-td>
      	</template>
      	
		<template v-slot:head(size)="data">
			Size
		</template>
		
		<template v-slot:cell(size)="data">
			{{ formatBytes(data.value) }}
		</template>
  
		<template v-slot:cell(progress)="data">
			<p v-if="data.item.success"><font-awesome-icon icon="check-circle" :style="{ color: '#c7e0af' }" size="2x"/></p>
			<button v-else-if="data.item.progress == '0.00'" @click="removeFileFromUploadQuery(data.item)">
			<font-awesome-icon icon="times-circle" :style="{ color: '#e2ae85' }" size="2x"/>
			</button>
			<b-progress variant="success" v-else :value="Number(data.value)" show-progress animated></b-progress>
		</template>
	</b-table>


	<b-table
		v-if="filesUploaded.length > 0" 
		id="uploaded-files" 
		class='files-tbl'
		:items="filesUploaded" 
  		:fields="fields_uploaded"
		:sort-by.sync="uploaded_sortBy"
      	:sort-desc.sync="uploaded_sortDesc">
      	
		<template v-slot:top-row="data">
          		<b-td colspan="4">
          			<b-progress :max="quotaMaximum">
      					<b-progress-bar :style="quotaProgressUploadedCssStyle" :value="quotaUsage" animated show-progress>{{ formatBytes(quotaUsage) }}</b-progress-bar>
      					<b-progress-bar variant="info" :value="quotaLeft" striped show-progress>{{ formatBytes(quotaLeft) }}</b-progress-bar>
    				</b-progress>
          		</b-td>
      	</template>
      	
		<template v-slot:cell(thumb)="data">
			<a :href="'/storage/media/' + data.item.id">
				<b-img height="75" :src="'/storage/thumbs/' + data.item.id"></b-img>
			</a>
		</template>

		<template v-slot:cell(size)="data">
			<p v-if="data.item">
				{{ formatBytes(data.item.size) }}
			</p>
		</template>
		
		<template v-slot:cell(delete)="data">
			<fill-up-button ref="deleteButton" v-on:do-it="deleteFile(data.item.id)">
			<font-awesome-icon icon="times-circle" :style="{ color: '#e2ae85' }" size="2x"/>
			</fill-up-button>
			</b-button>
		</template>
		
		<template v-slot:head(size)="data">
			Size
		</template>
	</b-table>
        
</div></div></div>
</template>

<script>

function pickHex(color1, color2, weight) {
    var w1 = weight;
    var w2 = 1 - w1;
    var rgb = [Math.round(color1[0] * w1 + color2[0] * w2),
        Math.round(color1[1] * w1 + color2[1] * w2),
        Math.round(color1[2] * w1 + color2[2] * w2)];
    return rgb;
};

import FillUpButton from './FillUpButton';

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
		    	  { key: 'progress', label: '', sortable: false }
		    	  ],
		      
		      filesUploaded: [],
		      fields_uploaded: [
		    	  { key: 'thumb', label: '', sortable: false },
		    	  { key: 'label', label: 'Name', sortable: true },
// 		          { key: 'mime_type', label: 'Type', sortable: true },
		          { key: 'size', label: 'Size', sortable: true },
		          { key: 'delete', label: '', sortable: false },
		          ],
		      uploaded_sortBy: 'label',
		      uploaded_sortDesc: false,
		      
		      quotaMaximum: 0,
		      quotaUsage: 0,
		      quotaLeft: 0,
		      
		      quotaColorGradient: {
		    	  min: [56,193,114],
		    	  max: [227,52,47],
		      },
		      
		      classObject: {
		    	    active: true,
		    	    'text-danger': false
				}
		      
		    }
		},
		computed: {
			quotatoUploadLeft: function() {
				return this.quotaLeft >= this.totalFilesSizeToUpload ? this.quotaLeft - this.totalFilesSizeToUpload : 0; 
			},
			
			quotaProgressToUploadCssStyle: function() {
				let weight = this.quotaLeft >= this.totalFilesSizeToUpload ? this.totalFilesSizeToUpload / this.quotaLeft : 1;
				let color = pickHex(this.quotaColorGradient.max, this.quotaColorGradient.min, weight);
				return {
					backgroundColor: `rgb(${color[0]}, ${color[1]}, ${color[2]})`
				};
			},
			
			quotaProgressUploadedCssStyle: function() {
				let weight = this.quotaMaximum >= this.quotaUsage ? this.quotaUsage / this.quotaMaximum : 1;
				let color = pickHex(this.quotaColorGradient.max, this.quotaColorGradient.min, weight);
				return {
					backgroundColor: `rgb(${color[0]}, ${color[1]}, ${color[2]})`
				};
			},
			
			uploadAction: function () {
				return '/api/files/upload?api_token=' + this.apiToken;
			},
			
			totalFilesSizeToUpload: function() {
				let result = true;
				
				this.files.forEach(function(file){
					result += file.size;
				});
				
				return result;
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
			FillUpButton
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
        	getQuota: function() {
        		self = this;
        		axios.get('/api/user/quota?api_token=' + this.apiToken)
        		  .then(function (response) {
        			  if (response.data.status == 'success') {
        				  self.quotaMaximum = response.data.maximum;
        				  self.quotaUsage = response.data.usage;
        				  self.quotaLeft = response.data.left;
        			  }
        		  })
        		  .catch(function (error) {
        		    console.log(error);
        		  });
        	},
        	
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
        				  self.getQuota();
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

<style scoped lang='scss'>
.progress {
	border-radius: 0;
	height: 1.5rem;
}
</style>
