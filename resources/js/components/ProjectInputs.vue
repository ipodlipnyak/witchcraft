<template>
<div>
	<simplebar class="bar" data-simplebar-auto-hide="false">
	<div class="row">
  
	<div class="col-6 pr-1">
		<div class="card">
			<div class="card-header">
			Inputs
			</div>
			<draggable
        		v-model="inputs"
        		v-bind="dragOptions"
        		class="list-group"
        		ghost-class="ghost"
        		@start="drag = true"
        		@end="drag = false"
        		tag="div">
				<transition-group type="transition" :name="!drag ? 'flip-list' : null">
        			<div
        				v-for="file in inputs"
          				class="list-group-item p-0"
          				:class="getInputClass(file)"
          				:key="file.id">
          				
          				<b-card 
          					no-body
          					class="overflow-hidden"
          					style="max-height: 3rem;"
          					
          					<b-row no-gutters>
          						<b-col md="12" lg="4">
        							<b-card-img :src="'/storage/thumbs/' + file.id" class="rounded-0"></b-card-img>
      							</b-col>
      							
      							<b-col md="12" lg="8">
      							<b-card-body :title="file.label ? file.label : file.name">
      							
      								<b-card-text>
      									<small>W:{{ file.width }} H:{{ file.height }}</small>
          								<small v-if="file.ratio">R:{{ file.ratio }}</small>
    								</b-card-text>
      							
      							</b-card-body>
      							</b-col>
          					</b-row>
    						
  						</b-card>
        			</div>
        			
      			</transition-group>
			</draggable>
		</div>

	</div>
	<div class="col-6 pl-1">
		<div class="card">
			<div class="card-header">
			Uploaded files
			</div>
			<draggable
        		v-model="filesUploaded"
        		v-bind="dragOptions"
        		class="list-group"
        		ghost-class="ghost"
        		@start="drag = true"
        		@end="drag = false"
        		tag="div">
				<transition-group type="transition" :name="!drag ? 'flip-list' : null">
        			<div
          				class="list-group-item p-0"
          				v-for="file in filesUploaded"
          				:key="file.id">
          				
          				<b-card 
          					no-body
          					class="overflow-hidden"
          					style="max-height: 3rem;"
          					
          					<b-row no-gutters>
          						<b-col md="12" lg="4">
        							<b-card-img :src="'/storage/thumbs/' + file.id" class="rounded-0"></b-card-img>
      							</b-col>
      							
      							<b-col md="12" lg="8">
      							<b-card-body class="overflow-hidden mr-1" :title="file.label ? file.label : file.name">
      							
      								<b-card-text>
      									<small>W:{{ file.width }} H:{{ file.height }}</small>
          								<small v-if="file.ratio">R:{{ file.ratio }}</small>
    								</b-card-text>
      							
      							</b-card-body>
      							</b-col>
          					</b-row>
    						
  						</b-card>
        			</div>
      			</transition-group>
			</draggable>
		</div>
	</div>
	
	</div>
	</simplebar>
</div>
</template>

<script>
import draggable from 'vuedraggable'
import { LayoutPlugin, CardPlugin, ImagePlugin, ListGroupPlugin } from 'bootstrap-vue'

import simplebar from 'simplebar-vue';
import 'simplebar/dist/simplebar.min.css';

Vue.use(LayoutPlugin)
Vue.use(CardPlugin)
Vue.use(ImagePlugin)
Vue.use(ListGroupPlugin)

export default {
	props: [
		'apiToken',
		'projectId'
		],
	data: function () {
		return {
			drag: false,
			filesUploaded: [],
			inputs: [],
		}
	},
	
	computed: {
		dragOptions() {
			return {
				animation: 200,
				group: "description",
				disabled: false,
				ghostClass: "ghost"
			};
		},
		
		inputOrder() {
			let result = [];
			
			this.inputs.forEach(function(el, i) {
				result.push(el.id);
			});
			
			return result;
		},
	},
	
	mounted() {
		this.init();
	},
	
	watch: {
		projectId: function (newVal, oldVal) {
			this.init();
		}
	},
	
	methods: {
		init: function() {
			this.filesUploaded = [];
			this.inputs = [];
			
			this.getFiles();
			this.getInputs();
		},
		
		getFiles: function() {
    		let self = this;
    		if (this.projectId) {
        		axios.get('/api/projects/' + this.projectId + '/files?api_token=' + this.apiToken)
        		.then(function (response) {
        			if (response.data.status == 'success') {
        				self.filesUploaded = response.data.files;
        			}
        		})
        		.catch(function (error) {
        			console.log(error);
        		});
    		} else {
        		axios.get('/api/projects/files?api_token=' + this.apiToken)
        		.then(function (response) {
        			if (response.data.status == 'success') {
        				self.filesUploaded = response.data.files;
        			}
        		})
        		.catch(function (error) {
        			console.log(error);
        		});
    		}

    	},
    	
		getInputs: function() {
    		let self = this;
    		if (this.projectId) {
        		axios.get('/api/projects/' + this.projectId + '/inputs?api_token=' + this.apiToken)
        		.then(function (response) {
        			if (response.data.status == 'success') {
        				self.inputs = response.data.files;
        			}
        		})
        		.catch(function (error) {
        			console.log(error);
        		});
    		}
    	},
    	
    	saveInputs: function (id = false) {
    		let self = this;
    		
    		let projectId = id ? id : this.projectId ? this.projectId : false;
    		
    		if (projectId) {
        		axios.post('/api/projects/' + projectId + '/inputs?api_token=' + this.apiToken, {
        			inputs: self.inputOrder
        		})
        		.then(function (response) {
        			if (response.data.status == 'success') {
        				self.init();
        			}
        		})
        		.catch(function (error) {
        			console.log(error);
        		});
    		}
    	},
    	
    	getInputClass: function (input) {
    		if (input.status && input.status == 1) {
    			return 'list-group-item-success';
    		} else if(input.status && input.status == 2) {
    			return 'list-group-item-danger';
    		} else if(input.status && input.status == 3) {
    			return 'list-group-item-warning';
    		} else if(input.status && input.status > 1) {
    			return 'list-group-item-info';
    		}
    		
    		return '';
    	},
		
		sort() {
			this.inputs = this.inputs.sort((a, b) => a.priority - b.priority);
		}
	},
	
    components: {
        draggable,
        simplebar
    },
}
</script>

<style scoped>

.flip-list-move {
  transition: transform 0.5s;
}
.no-move {
  transition: transform 0s;
}
.ghost {
  opacity: 0.5;
  background: #c8ebfb;
}
.list-group {
  min-height: 20px;
}
.list-group-item {
  cursor: move;
}
.list-group-item i {
  cursor: pointer;
}

.card {
	border-radius: 0;
	border: none;
}

.card-body {
	padding: 1rem;
}


.bar {
	height: 60vh;
	overflow-x: hidden;
}

</style>