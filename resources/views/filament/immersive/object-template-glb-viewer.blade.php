<div class="space-y-3">
    <p class="text-sm text-gray-500 dark:text-gray-400">
        Vista previa del modelo GLB cargado — así se ve el objeto que se usará en la escena.
    </p>

    <div
        wire:ignore
        x-data="{}"
        x-init="
            Promise.all([
                import('{{ asset('js/lib/voxel-plaza-engine.js') }}?v=3'),
                import('https://esm.sh/three@0.179.1/examples/jsm/controls/OrbitControls.js'),
                import('https://esm.sh/three@0.179.1/examples/jsm/loaders/GLTFLoader.js'),
            ]).then(([{ THREE }, { OrbitControls }, { GLTFLoader }]) => {
                const container = document.getElementById(@js('glb-viewer-'.$template->id));

                if (! container || container.dataset.initialized === 'true') {
                    return;
                }

                container.dataset.initialized = 'true';

                const scene = new THREE.Scene();
                scene.background = new THREE.Color(0xcfe8ff);

                const renderer = new THREE.WebGLRenderer({ antialias: true });
                renderer.shadowMap.enabled = true;
                container.appendChild(renderer.domElement);

                const camera = new THREE.PerspectiveCamera(50, 1, 0.1, 1000);
                const controls = new OrbitControls(camera, renderer.domElement);
                controls.enableDamping = true;

                scene.add(new THREE.AmbientLight(0xffffff, 0.9));
                const sun = new THREE.DirectionalLight(0xffffff, 1);
                sun.position.set(8, 12, 8);
                scene.add(sun);

                // Solo para esta previsualización: cuadrícula de referencia.
                // La experiencia inmersiva real nunca debe llevar esto.
                scene.add(new THREE.GridHelper(20, 20, 0x64748b, 0xcbd5e1));
                scene.add(new THREE.AxesHelper(2));

                function resizeRenderer() {
                    const width = Math.max(container.clientWidth, 320);
                    const height = Math.max(container.clientHeight, 480);
                    camera.aspect = width / height;
                    camera.updateProjectionMatrix();
                    renderer.setSize(width, height);
                }

                new ResizeObserver(() => resizeRenderer()).observe(container);
                resizeRenderer();

                new GLTFLoader().load(
                    @js($template->modelPathUrl()),
                    (gltf) => {
                        const model = gltf.scene;
                        model.traverse((node) => {
                            if (node.isMesh) {
                                node.castShadow = true;
                                node.receiveShadow = true;
                            }
                        });
                        scene.add(model);

                        const box = new THREE.Box3().setFromObject(model);
                        const size = box.getSize(new THREE.Vector3());
                        const center = box.getCenter(new THREE.Vector3());
                        const radius = Math.max(size.x, size.y, size.z, 1);

                        camera.position.set(center.x + radius, center.y + radius * 0.7, center.z + radius);
                        controls.target.copy(center);
                        controls.update();
                    },
                    undefined,
                    (error) => {
                        console.error(error);
                        container.innerHTML = '<p class=\'p-4 text-sm text-red-500\'>No se pudo cargar el modelo GLB.</p>';
                    },
                );

                function animate() {
                    requestAnimationFrame(animate);
                    controls.update();
                    renderer.render(scene, camera);
                }

                animate();
            })
        "
    >
        <div id="glb-viewer-{{ $template->id }}" style="width: 100%; min-height: 560px; border-radius: 0.75rem; overflow: hidden; background: #cfe8ff;"></div>
    </div>
</div>
