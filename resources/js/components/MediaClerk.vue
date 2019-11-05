<template>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">

  <ul>
    <li v-for="file in files">{{file.name}} - Error: {{file.error}}, Success: {{file.success}}</li>
  </ul>

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

const VueUploadComponent = require('vue-upload-component')

    export default {
	  	data: function () {
		    return {
		      files: [],
		      chunk: {
		          action: '/upload/chunk',
		          minSize: 1048576,
		          maxActive: 3,
		          maxRetries: 5
		      }
		    }
		},
		
		components: {
			FileUpload: VueUploadComponent
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
				
        	}
    	}
	}
</script>
