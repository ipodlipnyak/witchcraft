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
          				v-for="input in inputs"
          				:key="input.id">
          				{{ input.name }}
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
          				{{ file.name }}
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
		}
	},
	
	mounted() {
		this.getFiles();
		this.getInputs();
	},
	
	methods: {
		getFiles: function() {
    		self = this;
    		axios.get('/api/projects/' + this.projectId + '/files?api_token=' + this.apiToken)
    		.then(function (response) {
    			if (response.data.status == 'success') {
    				self.filesUploaded = response.data.files;
    			}
    		})
    		.catch(function (error) {
    			console.log(error);
    		});
    	},
    	
		getInputs: function() {
    		self = this;
    		axios.get('/api/projects/' + this.projectId + '/inputs?api_token=' + this.apiToken)
    		.then(function (response) {
    			if (response.data.status == 'success') {
    				self.inputs = response.data.files;
    			}
    		})
    		.catch(function (error) {
    			console.log(error);
    		});
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