<template>
<div class="container"><div class="row justify-content-center"><div class="col-md-8">

<!-- Edit selected project -->
<div v-if="projectSelected">
	<b-button-group  size="bg" class="btn-block">
		<b-button squared @click="updateProject" variant="success" class="mb-4">Save</b-button>
		<b-button squared @click="selectProject('')" variant="primary" class="mb-4">Close</b-button>
    </b-button-group>
</div>

<!-- New project -->
<div v-else-if="projectSelected === 0">
	<b-button-group  size="bg" class="btn-block">
		<b-button squared @click="saveProject" variant="success" class="mb-4">Save</b-button>
		<b-button squared @click="selectProject('')" variant="primary" class="mb-4">Close</b-button>
    </b-button-group>
</div>

<div v-if="projectSelected || projectSelected === 0">
	<b-input-group size="md" prepend="Name">
    	<b-form-input v-model="outputLabel"></b-form-input>
		<b-input-group-append>
			<b-button v-for="ext in fileExtensionsList"
				:key="ext"
				@click="outputFileExtension = ext" 
				:variant="outputFileExtension == ext ? 'primary' : 'outline-primary'">
				.{{ ext }}
			</b-button>
		</b-input-group-append>
	</b-input-group>
	
	<b-input-group size="md" prepend="Ratio" class="pt-4">
		<b-form-input v-model="aspectWidth"></b-form-input>
		<b-form-input v-model="aspectHeight"></b-form-input>
		<template v-slot:append>
			<b-dropdown text="Presets" variant="primary">
				<b-dropdown-item v-for="set in aspectPresets" 
					:key="set.name" 
					@click="selectPreset(set)">
					{{ set.name }}
				</b-dropdown-item>
			</b-dropdown>
    	</template>
	</b-input-group>
	
	<project-inputs ref="inputs" 
		:api-token="apiToken" 
		:project-id="projectSelected.id">
	</project-inputs>
</div>

<!-- Projects listing -->
<div v-else>
	<div>
		<b-button block squared @click="selectProject(0)" variant="primary">New project</b-button>
	</div>
	<div v-for="(project, index) in projects" :key="project.id">
		<b-button block squared @click="selectProject(project.id)" variant="primary">{{ project.output.label }}</b-button>
	</div>
</div>


</div></div></div>
</template>

<script>
import ProjectInputs from './ProjectInputs';
import { InputGroupPlugin, FormInputPlugin, ButtonPlugin, ListGroupPlugin, CardPlugin, DropdownPlugin } from 'bootstrap-vue';

Vue.use(InputGroupPlugin)
Vue.use(FormInputPlugin)
Vue.use(ButtonPlugin)
Vue.use(ListGroupPlugin)
Vue.use(CardPlugin)
Vue.use(DropdownPlugin)

export default {
	props: ['apiToken'],
	data: function () {
		return {
			projects: [],
			
			projectSelected: '',
			outputLabel: '',
			outputFileExtension: '',
			
			fileExtensionsList: ['mkv', 'mp4'],
			
			aspectWidth: 0,
			aspectHeight: 0,
			
			aspectPresets: [
				{
					name: 'Full HD',
					w: 1920,
					h: 1080,
				},
				{
					name: 'HD',
					w: 1280,
					h: 720,
				},
				{
					name: 'WVGA',
					w: 848,
					h: 480,
				},
			],
			
		}
	},
	
	computed: {
		defaultAspectPreset() {
			return {
				w: 848,
				h: 480,
			}
		},
		
		defaultExtension() {
			return 'mkv';
		},
		
		defaultOutputName() {
			return 'output';
		},
		
		outputName() {
			return this.outputLabel && this.outputFileExtension ? this.outputLabel + '.' + this.outputFileExtension : '';
		},
	},
		
	components: {
		ProjectInputs,
	},
		
	mounted() {
		this.getProjects();
		this.initProject();
	},
	
	watch: {
		projectSelected: function (newVal, oldVal) {
			this.initProject();
		}
	},
	
	methods: {
		initProject: function() {
			let self = this;
			
			self.outputLabel = self.projectSelected.output ? self.projectSelected.output.label : self.defaultOutputName;
			self.outputFileExtension = self.projectSelected.output ? self.projectSelected.output.name.split('.').pop() : self.defaultExtension;
			
			if(self.projectSelected.output && self.projectSelected.output.width && self.projectSelected.output.height) {
				self.aspectWidth = self.projectSelected.output.width;
				self.aspectHeight = self.projectSelected.output.height;
			} else {
				self.aspectWidth = self.defaultAspectPreset.w;
				self.aspectHeight = self.defaultAspectPreset.h;
			}
		},
		
		saveProject: function() {
			self = this;
			
			axios.post('/api/projects?api_token=' + this.apiToken, {
				label: self.outputLabel,
				extension: self.outputFileExtension,
				width: self.aspectWidth,
				height: self.aspectHeight,
			})
    		.then(function (response) {
    			if (response.data.status == 'success') {
    				self.$refs.inputs.saveInputs(response.data.id);
        			self.getProjects();
    			}
    		})
    		.catch(function (error) {
    			console.log(error);
    		});
		},
		
		updateProject: function() {
			self = this;
			self.$refs.inputs.saveInputs();
			
			axios.post('/api/projects/' + self.projectSelected.id + '?api_token=' + this.apiToken, {
				label: self.outputLabel,
				extension: self.outputFileExtension,
				width: self.aspectWidth,
				height: self.aspectHeight,
			})
    		.then(function (response) {
    			self.getProjects();
    		})
    		.catch(function (error) {
    			console.log(error);
    		});
			
		},
    	
    	getProjects: function() {
    		self = this;
    		axios.get('/api/projects?api_token=' + this.apiToken)
    		.then(function (response) {
    			self.projects = response.data;
    		})
    		.catch(function (error) {
    			console.log(error);
    		});
    	},
    	
    	selectProject: function(id) {
    		self = this;
    		
    		if (id > 0) {
    			this.$emit('lock-swipe');
        		axios.get('/api/projects/' + id + '?api_token=' + this.apiToken)
        		.then(function (response) {
        			if (response.data.status == 'success') {
        				self.projectSelected = response.data.project;
        			}
        		})
        		.catch(function (error) {
        			console.log(error);
        		});
    		} else if (id === 0) {
    			this.$emit('lock-swipe');
    			self.projectSelected = id;
    		} else {
    			this.$emit('unlock-swipe');
    			self.projectSelected = id;
    		}
    	},
    	
    	selectPreset: function(set) {
    		this.aspectHeight = set.h;
    		this.aspectWidth = set.w;
    	},
	},
}
</script>

<style scoped>
</style>
