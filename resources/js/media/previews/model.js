import * as THREE from 'three';
import {OrbitControls} from 'three/addons/controls/OrbitControls.js';
import {STLLoader} from 'three/addons/loaders/STLLoader.js';
import {OBJLoader} from 'three/addons/loaders/OBJLoader.js';

export async function mount(manager, item) {
    if (Number(item.size) > 32 * 1024 * 1024) throw new Error(manager.previewLabels().too_large);
    const generation = manager.previewGeneration;
    const response = await fetch(item.url);
    if (!response.ok) throw new Error(manager.previewLabels().failed);
    const bytes = await response.arrayBuffer();
    if (manager.previewGeneration !== generation) return;
    const stage = manager.elements.previewStage;
    stage.classList.add('is-model'); stage.replaceChildren();
    const scene = new THREE.Scene(); scene.background = new THREE.Color('#0b1424');
    const material = new THREE.MeshStandardMaterial({color:'#9aaaff',metalness:.15,roughness:.55,side:THREE.DoubleSide});
    const model = item.extension.toLowerCase() === 'stl'
        ? new THREE.Mesh(new STLLoader().parse(bytes), material)
        : new OBJLoader().parse(new TextDecoder().decode(bytes));
    model.traverse(object => {if(object.isMesh) {if(object.material !== material) (Array.isArray(object.material)?object.material:[object.material]).forEach(value => value.dispose()); object.material = material;}});
    const box = new THREE.Box3().setFromObject(model), center=box.getCenter(new THREE.Vector3()), size=box.getSize(new THREE.Vector3());
    const extent = Math.max(size.x,size.y,size.z);
    if (!Number.isFinite(extent) || extent <= 0) {material.dispose(); throw new Error(manager.previewLabels().failed);}
    model.position.sub(center).multiplyScalar(2/extent); model.scale.setScalar(2/extent); scene.add(model);
    scene.add(new THREE.HemisphereLight(0xffffff,0x344063,3));
    const light = new THREE.DirectionalLight(0xffffff,3);light.position.set(3,5,4);scene.add(light);
    const camera = new THREE.PerspectiveCamera(45,1,.01,100);camera.position.set(3,2,3);
    const renderer = new THREE.WebGLRenderer({antialias:true});renderer.setPixelRatio(Math.min(devicePixelRatio,2));stage.append(renderer.domElement);
    const controls = new OrbitControls(camera,renderer.domElement);controls.enableDamping=true;controls.minDistance=.3;controls.maxDistance=15;
    const reset = document.createElement('button');reset.type='button';reset.className='fm-model-reset';reset.textContent=manager.previewLabels().reset_view;
    reset.onclick=()=>{camera.position.set(3,2,3);controls.target.set(0,0,0);controls.update();};stage.append(reset);
    const resize=()=>{const {width,height}=stage.getBoundingClientRect();renderer.setSize(width,height);camera.aspect=width/Math.max(height,1);camera.updateProjectionMatrix();};
    const observer=new ResizeObserver(resize);observer.observe(stage);resize();
    renderer.setAnimationLoop(()=>{controls.update();renderer.render(scene,camera);});
    return ()=>{observer.disconnect();renderer.setAnimationLoop(null);controls.dispose();model.traverse(object=>object.geometry?.dispose());material.dispose();renderer.dispose();renderer.forceContextLoss();};
}
