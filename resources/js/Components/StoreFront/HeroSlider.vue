<script setup>
import { Link } from '@inertiajs/vue3';
import { Autoplay } from 'swiper/modules';
import { Swiper, SwiperSlide } from 'swiper/vue';
import 'swiper/css';

const slides = [
    {
        image: 'https://images.pexels.com/photos/30703877/pexels-photo-30703877.jpeg?auto=compress&cs=tinysrgb&w=1920',
        title: 'Discover Traditional Elegance',
        subtitle:
            'Curated collections blending heritage with modern style for the fashion-forward.',
        cta: 'Explore Collection',
        ctaLink: route('products.index'),
    },
    {
        image: 'https://images.pexels.com/photos/15751052/pexels-photo-15751052.jpeg?auto=compress&cs=tinysrgb&w=1920',
        title: 'Modest Fashion, Bold Style',
        subtitle:
            'Express yourself with our latest modest wear collection, designed for confidence.',
        cta: 'Shop Now',
        ctaLink: route('products.index'),
    },
    {
        image: 'https://images.pexels.com/photos/35618862/pexels-photo-35618862.jpeg?auto=compress&cs=tinysrgb&w=1920',
        title: 'Glamorous Nights',
        subtitle:
            'Stand out with our premium evening wear, crafted for unforgettable moments.',
        cta: 'Shop Evening Wear',
        ctaLink: route('products.index'),
    },
    {
        image: 'https://images.pexels.com/photos/10411933/pexels-photo-10411933.jpeg?auto=compress&cs=tinysrgb&w=1920',
        title: 'Sharp & Sophisticated',
        subtitle:
            "Elevate your everyday look with our curated men's collection.",
        cta: "Shop Men's Fashion",
        ctaLink: route('products.index'),
    },
];

const modules = [Autoplay];
</script>

<template>
    <section
        class="relative mb-16 select-none"
        role="region"
        aria-label="Hero slideshow"
    >
        <Swiper
            :modules="modules"
            :loop="true"
            :autoplay="{
                delay: 5000,
                disableOnInteraction: false,
            }"
            class="swiper-custom rounded-2xl lg:aspect-[16/9]"
        >
            <SwiperSlide
                v-for="(s, i) in slides"
                :key="i"
                class="relative h-[400px] md:h-[500px] lg:h-full"
            >
                <img
                    :src="s.image"
                    :alt="s.title"
                    class="absolute inset-0 h-full w-full object-cover"
                    loading="lazy"
                    draggable="false"
                />
                <div
                    class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/30 to-black/10"
                ></div>
                <div
                    class="absolute inset-0 bg-gradient-to-r from-black/50 via-transparent to-transparent"
                ></div>
                <div
                    class="relative z-10 flex h-full items-center px-6 md:px-10 lg:px-14"
                >
                    <div class="max-w-lg">
                        <h1
                            class="text-3xl font-bold leading-tight text-white md:text-4xl lg:text-5xl"
                        >
                            {{ s.title }}
                        </h1>
                        <p
                            class="mt-3 max-w-md text-sm text-white/80 md:text-base lg:text-lg"
                        >
                            {{ s.subtitle }}
                        </p>
                        <Link
                            :href="s.ctaLink"
                            class="mt-6 inline-flex items-center gap-2 rounded-lg bg-yellow-400 px-7 py-3 text-sm font-semibold text-gray-900 transition hover:opacity-90 hover:shadow-lg hover:shadow-yellow-500/25 md:text-base"
                            >{{ s.cta }}</Link
                        >
                    </div>
                </div>
            </SwiperSlide>
        </Swiper>
    </section>
</template>
