<template>
<div class="row pt-4">
	<div class="col-6">
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
          				class="list-group-item"
          				:class="getInputClass(input)"
          				v-for="input in inputs"
          				:key="input.id">
          				<p class="mb-1">{{ input.label ? input.label : input.name }}</p> 
          				<small>W:{{ input.width }} H:{{ input.height }}</small>
          				<small>R:{{ input.ratio }}</small>
        			</div>
      			</transition-group>
			</draggable>
		</div>

	</div>
	<div class="col-6">
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
          				class="list-group-item"
          				v-for="file in filesUploaded"
          				:key="file.id">
          				<p class="mb-1">{{ file.label ? file.label : file.name }}</p> 
          				<small>W:{{ file.width }} H:{{ file.height }}</small>
          				<small v-if="file.ratio">R:{{ file.ratio }}</small>
        			</div>
      			</transition-group>
			</draggable>
		</div>
	</div>
</div>
</template>

<script>
import draggable from 'vuedraggable'
import { ListGroupPlugin } from 'bootstrap-vue'

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
</style>