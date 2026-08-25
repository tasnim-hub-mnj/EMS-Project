import * as THREE from './vendor/three.module.js';
import { OrbitControls } from './vendor/OrbitControls.js';
import { GLTFLoader } from './vendor/GLTFLoader.js';

const root = document.getElementById('app');
const loading = document.getElementById('loading');
const errorBox = document.getElementById('error');
const scene = new THREE.Scene();
const camera = new THREE.PerspectiveCamera(46, 1, 0.01, 1000);
const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: false, logarithmicDepthBuffer: true });
renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
renderer.outputColorSpace = THREE.SRGBColorSpace;
root.appendChild(renderer.domElement);

const controls = new OrbitControls(camera, renderer.domElement);
controls.enableDamping = true;
controls.dampingFactor = 0.08;
controls.maxPolarAngle = Math.PI / 2.05;
controls.minDistance = 0.8;
controls.maxDistance = 100;

scene.add(new THREE.HemisphereLight(0xffffff, 0x20203d, 1.8));
const keyLight = new THREE.DirectionalLight(0xffffff, 2.2);
keyLight.position.set(4, 9, 6);
scene.add(keyLight);
const fillLight = new THREE.DirectionalLight(0xa855f7, 1.1);
fillLight.position.set(-5, 4, -4);
scene.add(fillLight);

const mapRoot = new THREE.Group();
scene.add(mapRoot);
const loader = new GLTFLoader();
const objects = new Map();
let selectedId = null;
let currentPayload = null;
const WORLD_SCALE = 0.01;

function color(value, fallback = 0x7a1fff) {
  try { return new THREE.Color(value || fallback); } catch (_) { return new THREE.Color(fallback); }
}

function clearMap() {
  while (mapRoot.children.length) {
    const child = mapRoot.children.pop();
    child?.traverse((node) => {
      if (node.geometry) node.geometry.dispose();
      if (node.material) {
        const materials = Array.isArray(node.material) ? node.material : [node.material];
        materials.forEach((material) => material.dispose());
      }
    });
  }
  objects.clear();
}

function roundedShape(width, depth, radius) {
  const shape = new THREE.Shape();
  const x = width / 2;
  const z = depth / 2;
  const r = Math.min(radius, x - 0.001, z - 0.001);
  shape.moveTo(-x + r, -z);
  shape.lineTo(x - r, -z);
  shape.quadraticCurveTo(x, -z, x, -z + r);
  shape.lineTo(x, z - r);
  shape.quadraticCurveTo(x, z, x - r, z);
  shape.lineTo(-x + r, z);
  shape.quadraticCurveTo(-x, z, -x, z - r);
  shape.lineTo(-x, -z + r);
  shape.quadraticCurveTo(-x, -z, -x + r, -z);
  return shape;
}

function addFloor(width, depth, background, boundaryColor) {
  scene.background = color(background, 0x090b18);
  const worldWidth = width * WORLD_SCALE;
  const worldDepth = depth * WORLD_SCALE;
  const floorShape = roundedShape(worldWidth, worldDepth, Math.min(worldWidth, worldDepth) * 0.055);
  const floor = new THREE.Mesh(
    new THREE.ShapeGeometry(floorShape),
    new THREE.MeshStandardMaterial({ color: 0x2f2f32, roughness: 0.85, metalness: 0.02 })
  );
  floor.rotation.x = -Math.PI / 2;
  floor.position.set(worldWidth / 2, 0, worldDepth / 2);
  mapRoot.add(floor);
  const outer = roundedShape(worldWidth, worldDepth, Math.min(worldWidth, worldDepth) * 0.06)
    .getPoints(120).map((point) => new THREE.Vector3(point.x + worldWidth / 2, 0.026, point.y + worldDepth / 2));
  const inner = roundedShape(worldWidth - 0.28, worldDepth - 0.28, Math.max(0.1, Math.min(worldWidth, worldDepth) * 0.055))
    .getPoints(120).map((point) => new THREE.Vector3(point.x + worldWidth / 2, 0.027, point.y + worldDepth / 2));
  outer.push(outer[0].clone());
  inner.push(inner[0].clone());
  const frameMaterial = new THREE.LineBasicMaterial({ color: color(boundaryColor, 0xa855f7) });
  mapRoot.add(new THREE.Line(new THREE.BufferGeometry().setFromPoints(outer), frameMaterial));
  mapRoot.add(new THREE.Line(new THREE.BufferGeometry().setFromPoints(inner), frameMaterial));
}

function addFallback(instance, position, scale) {
  const material = new THREE.MeshStandardMaterial({ color: color(instance.color || instance.fill), roughness: 0.45, metalness: 0.25 });
  const mesh = new THREE.Mesh(new THREE.BoxGeometry(Math.max(scale.x, 0.18), Math.max(scale.y, 0.18), Math.max(scale.z, 0.18)), material);
  mesh.position.copy(position);
  mapRoot.add(mesh);
  return mesh;
}

function modelTransform(instance, object) {
  const raw = instance.scale || {};
  const target = new THREE.Vector3(
    Number(raw.x) || Number(instance.width) || Number(instance.dimensions?.x) || 1,
    Number(raw.y) || Number(instance.depth) || Number(instance.dimensions?.y) || 1,
    Number(raw.z) || Number(instance.height) || Number(instance.dimensions?.z) || 1,
  );
  const key = String(instance.asset_key || '').toLowerCase()
    .replace(/^booth_/, '').replace(/\.(glb|gltf|obj|fbx|stl)$/, '');
  let natural = new THREE.Vector3(1, 1, 1);
  let verticalOffset = 0;
  if (/^mod[1-5]$/.test(key)) {
    natural = new THREE.Vector3(0.6905, 0.6011, 0.7004);
    verticalOffset = key === 'mod4' || key === 'mod5' ? 0.04 : 0;
  } else if (/^meet[1-3]$/.test(key)) {
    natural = new THREE.Vector3(0.82, 0.9, 0.82);
  }
  object.scale.set(
    target.x / natural.x,
    target.y / natural.y,
    target.z / natural.z,
  );
  const floorY = Number(instance.position?.y) || 0;
  const isGate = key === 'gate' || key === 'entrance_gate';
  object.position.set(
    (Number(instance.position?.x) || 0) + target.x / 2,
    floorY + (isGate ? target.y * 0.15 : target.y / 2) - verticalOffset,
    (Number(instance.position?.z) || 0) + target.z / 2,
  );
}

function applyInstanceColor(object, value) {
  const tint = color(value, 0x7a1fff);
  object.traverse((node) => {
    if (!node.isMesh || !node.material) return;
    const wasArray = Array.isArray(node.material);
    const materials = wasArray ? node.material : [node.material];
    const tinted = materials.map((material) => {
      const copy = material.clone();
      if (copy.color) copy.color.copy(tint);
      if (copy.emissive) {
        copy.userData.expoOriginalEmissive = copy.emissive.clone();
        copy.userData.expoOriginalEmissiveIntensity = copy.emissiveIntensity;
        copy.emissive.copy(tint);
        copy.emissiveIntensity = 0.18;
      }
      return copy;
    });
    node.material = wasArray ? tinted : tinted[0];
  });
}

function addProceduralInstance(instance) {
  const key = String(instance.asset_key || '').toLowerCase().replace(/^booth_/, '');
  if (!['wall_section', 'hall_section', 'floor_plate', 'section', 'wing_hall'].includes(key)) {
    return null;
  }
  const dimensions = instance.dimensions || {};
  // Organizer export axes: x=width, z=floor depth, y=model thickness.
  const width = Number(instance.scale?.x) || Number(instance.width) || Number(dimensions.x) || 1;
  const floorDepth = Number(instance.scale?.z) || Number(instance.depth) || Number(dimensions.z) || 1;
  const thickness = Number(instance.scale?.y) || Number(instance.height) || Number(dimensions.y) || 1;
  const isSection = key === 'wall_section' || key === 'hall_section' || key === 'section';
  const tint = color(isSection ? (instance.stroke || instance.color || instance.fill) : (instance.fill || instance.color), 0xa855f7);
  const group = new THREE.Group();
  const floorMaterial = new THREE.MeshStandardMaterial({
    color: tint,
    transparent: true,
    opacity: 0.18,
    roughness: 0.8,
    side: THREE.DoubleSide,
  });
  const floor = new THREE.Mesh(new THREE.PlaneGeometry(width, floorDepth), floorMaterial);
  floor.rotation.x = -Math.PI / 2;
  floor.position.y = 0.01;
  group.add(floor);
  const frameCount = key === 'hall_section' ? 3 : 2;
  for (let index = 0; index < frameCount; index++) {
    const inset = index * (key === 'hall_section' ? 0.055 : 0.065);
    const frameWidth = width - inset * 2;
    const frameDepth = floorDepth - inset * 2;
    if (frameWidth <= 0 || frameDepth <= 0) continue;
    const points = roundedShape(frameWidth, frameDepth, Math.min(0.1, frameWidth / 4, frameDepth / 4))
      .getPoints(64)
      .map((point) => new THREE.Vector3(point.x, 0.018 - index * 0.006, point.y));
    points.push(points[0].clone());
    const frameMaterial = new THREE.LineBasicMaterial({ color: tint });
    group.add(new THREE.Line(new THREE.BufferGeometry().setFromPoints(points), frameMaterial));
  }
  if (key === 'wing_hall') {
    const wingColor = 0x00d4ff;
    const supportMaterial = new THREE.MeshStandardMaterial({
      color: wingColor,
      emissive: wingColor,
      emissiveIntensity: 0.45,
      metalness: 0.8,
      roughness: 0.08,
    });
    const postSize = 0.04;
    for (const [x, z] of [
      [-width * 0.25, -floorDepth * 0.25],
      [width * 0.25, -floorDepth * 0.25],
      [-width * 0.25, floorDepth * 0.25],
      [width * 0.25, floorDepth * 0.25],
    ]) {
      const post = new THREE.Mesh(new THREE.BoxGeometry(postSize, thickness, postSize), supportMaterial);
      post.position.set(x, thickness / 2, z);
      group.add(post);
    }
    const brace = new THREE.Mesh(new THREE.BoxGeometry(width * 0.96, 0.025, 0.025), supportMaterial);
    brace.position.y = thickness * 0.55;
    group.add(brace);
    const crossBrace = new THREE.Mesh(new THREE.BoxGeometry(0.025, 0.025, floorDepth * 0.96), supportMaterial);
    crossBrace.position.y = thickness * 0.55;
    group.add(crossBrace);
  }
  group.position.set(
    (Number(instance.position?.x) || 0) + width / 2,
    Number(instance.position?.y) || 0,
    (Number(instance.position?.z) || 0) + floorDepth / 2,
  );
  group.rotation.y = Number(instance.rotation?.y) || 0;
  group.userData.mapId = String(instance.id || '');
  return group;
}

async function addInstance(instance, assets) {
  const position = new THREE.Vector3(Number(instance.position?.x) || 0, Number(instance.position?.y) || 0, Number(instance.position?.z) || 0);
  const dimensions = instance.dimensions || {};
  const scale = new THREE.Vector3(
    Number(dimensions.x) || Number(instance.scale?.x) || 1,
    Number(dimensions.y) || Number(instance.scale?.y) || 1,
    Number(dimensions.z) || Number(instance.scale?.z) || 1,
  );
  const rotation = instance.rotation || {};
  const procedural = addProceduralInstance(instance);
  if (procedural) {
    mapRoot.add(procedural);
    objects.set(procedural.userData.mapId, procedural);
    return;
  }
  const url = assets[instance.asset_key] || (String(instance.asset_key || '').match(/\.(glb|gltf)(\?|$)/i) ? instance.asset_key : '');
  let object;
  if (url) {
    try {
      const gltf = await loader.loadAsync(url);
      object = gltf.scene;
      modelTransform(instance, object);
      object.rotation.set(Number(rotation.x) || 0, Number(rotation.y) || 0, Number(rotation.z) || 0);
      applyInstanceColor(object, instance.color || instance.fill);
      mapRoot.add(object);
    } catch (_) {
      object = addFallback(instance, position, scale);
    }
  } else {
    object = addFallback(instance, position, scale);
    object.position.set(
      position.x + scale.x / 2,
      position.y + scale.y / 2,
      position.z + scale.z / 2,
    );
  }
  object.userData.mapId = String(instance.id || '');
  object.userData.label = instance.label || instance.id || '';
  object.traverse((child) => { child.userData.mapId = object.userData.mapId; });
  objects.set(object.userData.mapId, object);
}

function frameMap(width, depth) {
  const box = new THREE.Box3().setFromObject(mapRoot);
  const center = box.getCenter(new THREE.Vector3());
  const size = box.getSize(new THREE.Vector3());
  const radius = Math.max(size.x, size.z, Math.max(width, depth) * WORLD_SCALE) * 0.62;
  controls.target.copy(center);
  camera.position.set(center.x + radius * 0.95, center.y + radius * 0.82, center.z + radius * 1.15);
  controls.minDistance = Math.max(radius * 0.15, 0.2);
  controls.maxDistance = Math.max(radius * 8, 8);
  controls.update();
}

window.setExpoScene = async (value) => {
  try {
    currentPayload = typeof value === 'string' ? JSON.parse(value) : value;
    const sceneData = currentPayload?.scene || {};
    const width = Number(sceneData.width) || 1200;
    const depth = Number(sceneData.height) || 800;
    clearMap();
    const instances = Array.isArray(currentPayload?.instances) ? currentPayload.instances : [];
    const assets = currentPayload?.assets || {};
    const gate = instances.find((item) => {
      const key = String(item.asset_key || '').toLowerCase();
      return key === 'gate' || key === 'entrance_gate' || key === 'booth_gate';
    });
    addFloor(width, depth, sceneData.background_color, gate?.color || gate?.fill);
    loading.style.display = 'grid';
    errorBox.hidden = true;
    await Promise.all(instances.map((instance) => addInstance(instance, assets)));
    frameMap(width, depth);
    loading.style.display = 'none';
    requestRender();
  } catch (error) {
    loading.style.display = 'none';
    errorBox.hidden = mapRoot.children.length > 0;
    errorBox.textContent = `Unable to load the 3D map: ${error?.message || error}`;
    console.error('Expo map render failed', error);
  }
};

window.setExpoSelected = (id) => {
  selectedId = String(id || '');
  objects.forEach((object, objectId) => {
    object.traverse((node) => {
      if (!node.material || !node.material.emissive) return;
      const material = node.material;
      if (objectId === selectedId) {
        material.emissive.set(0xffd700);
        material.emissiveIntensity = 0.35;
        return;
      }
      const original = material.userData.expoOriginalEmissive;
      if (original) material.emissive.copy(original);
      material.emissiveIntensity =
        material.userData.expoOriginalEmissiveIntensity ?? 0;
    });
  });
  requestRender();
};

const raycaster = new THREE.Raycaster();
const pointer = new THREE.Vector2();
let renderPending = false;

controls.addEventListener('change', requestRender);

function requestRender() {
  if (renderPending) return;
  renderPending = true;
  requestAnimationFrame(() => {
    renderPending = false;
    const stillMoving = controls.update();
    renderer.render(scene, camera);
    if (stillMoving) requestRender();
  });
}

renderer.domElement.addEventListener('pointerup', (event) => {
  const rect = renderer.domElement.getBoundingClientRect();
  pointer.x = ((event.clientX - rect.left) / rect.width) * 2 - 1;
  pointer.y = -((event.clientY - rect.top) / rect.height) * 2 + 1;
  raycaster.setFromCamera(pointer, camera);
  const hits = raycaster.intersectObjects([...objects.values()], true);
  const hit = hits[0]?.object;
  const id = hit?.userData.mapId;
  if (id) window.SceneBridge?.postMessage(JSON.stringify({
    type: 'elementTap',
    id,
    x: event.clientX - rect.left,
    y: event.clientY - rect.top
  }));
});

function resize() {
  const width = root.clientWidth || 1;
  const height = root.clientHeight || 1;
  camera.aspect = width / height;
  camera.updateProjectionMatrix();
  renderer.setSize(width, height, false);
  requestRender();
}
window.addEventListener('resize', resize);
resize();
window.SceneBridge?.postMessage(JSON.stringify({ type: 'sceneReady' }));
