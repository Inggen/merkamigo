@props([
    'template',
    'standColorModel' => 'stand_color',
])

@php($viewerId = 'stand-template-glb-preview-'.$template->id)

<div
    x-data="{
        standColor: $wire.entangle(@js($standColorModel)).live,
        errorMessage: null,
        applyColor: null,
        initViewer() {
            let modelRoot = null;

            Promise.all([
                import('https://esm.sh/three@0.179.1'),
                import('{{ asset('js/lib/stand-color-utils.js') }}?v=2'),
                import('https://esm.sh/three@0.179.1/examples/jsm/loaders/GLTFLoader.js'),
            ]).then(([THREE, { applyStandPrimaryColor }, { GLTFLoader }]) => {
                const container = document.getElementById(@js($viewerId));

                if (! container || container.dataset.initialized === 'true') {
                    return;
                }

                container.dataset.initialized = 'true';

                const scene = new THREE.Scene();
                scene.background = new THREE.Color(0xf5f7fb);

                const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: false });
                renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
                container.appendChild(renderer.domElement);

                const camera = new THREE.PerspectiveCamera(40, 1, 0.1, 1000);
                scene.add(new THREE.AmbientLight(0xffffff, 1.15));

                const keyLight = new THREE.DirectionalLight(0xffffff, 1.1);
                keyLight.position.set(8, 10, 8);
                scene.add(keyLight);

                const fillLight = new THREE.DirectionalLight(0xffffff, 0.5);
                fillLight.position.set(-6, 5, -4);
                scene.add(fillLight);

                scene.add(new THREE.GridHelper(16, 16, 0xd1d5db, 0xe5e7eb));

                this.applyColor = (color) => {
                    if (! modelRoot) {
                        return;
                    }

                    applyStandPrimaryColor(modelRoot, color);
                };

                function resizeRenderer() {
                    const width = Math.max(container.clientWidth, 220);
                    const height = Math.max(container.clientHeight, 220);
                    camera.aspect = width / height;
                    camera.updateProjectionMatrix();
                    renderer.setSize(width, height);
                }

                const resizeObserver = new ResizeObserver(() => resizeRenderer());
                resizeObserver.observe(container);
                resizeRenderer();

                new GLTFLoader().load(
                    @js($template->modelPathUrl()),
                    (gltf) => {
                        try {
                            modelRoot = new THREE.Group();
                            const model = gltf.scene;
                            const targetSize = 3.4;

                            model.traverse((node) => {
                                if (node.isMesh) {
                                    node.castShadow = true;
                                    node.receiveShadow = true;
                                }
                            });

                            model.updateMatrixWorld(true);

                            const initialBox = new THREE.Box3().setFromObject(model);
                            const initialSize = initialBox.getSize(new THREE.Vector3());
                            const maxDimension = Math.max(initialSize.x, initialSize.y, initialSize.z, 0.001);
                            const fitScale = targetSize / maxDimension;

                            model.scale.setScalar(fitScale);
                            model.updateMatrixWorld(true);

                            const fittedBox = new THREE.Box3().setFromObject(model);
                            const center = fittedBox.getCenter(new THREE.Vector3());

                            model.position.x -= center.x;
                            model.position.y -= fittedBox.min.y;
                            model.position.z -= center.z;
                            model.updateMatrixWorld(true);

                            const finalBox = new THREE.Box3().setFromObject(model);
                            const finalSize = finalBox.getSize(new THREE.Vector3());
                            const radius = Math.max(finalSize.x, finalSize.y, finalSize.z, 1);

                            modelRoot.add(model);
                            scene.add(modelRoot);
                            this.applyColor(this.standColor);

                            camera.near = 0.01;
                            camera.far = 200;
                            camera.updateProjectionMatrix();
                            camera.position.set(radius * 1.2, Math.max(radius * 0.85, 1.9), radius * 1.2);
                            camera.lookAt(0, Math.max(finalSize.y * 0.45, 0.9), 0);
                        } catch (error) {
                            console.error(error);
                            this.errorMessage = 'No se pudo preparar el modelo 3D.';
                        }
                    },
                    undefined,
                    (error) => {
                        console.error(error);
                        this.errorMessage = 'No se pudo cargar el modelo 3D.';
                    },
                );

                const animate = () => {
                    requestAnimationFrame(animate);

                    if (modelRoot) {
                        modelRoot.rotation.y += 0.01;
                    }

                    renderer.render(scene, camera);
                };

                animate();
            }).catch((error) => {
                console.error(error);
                this.errorMessage = 'No se pudo iniciar la vista previa 3D.';
            });
        },
    }"
    x-init="initViewer()"
    x-effect="if (applyColor) { applyColor(standColor); }"
    class="relative aspect-square w-full overflow-hidden rounded-2xl border border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900"
>
    <div id="{{ $viewerId }}" wire:ignore class="h-full w-full"></div>
    <div
        x-cloak
        x-show="errorMessage"
        x-text="errorMessage"
        class="pointer-events-none absolute inset-0 flex items-center justify-center px-6 text-center text-sm text-zinc-500"
    ></div>
</div>
