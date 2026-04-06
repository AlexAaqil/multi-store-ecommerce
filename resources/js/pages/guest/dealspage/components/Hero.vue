<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';

interface Props {
    initialHours?: number;
    initialMinutes?: number;
    initialSeconds?: number;
}

const props = withDefaults(defineProps<Props>(), {
    initialHours: 8,
    initialMinutes: 42,
    initialSeconds: 17,
});

const hours = ref(props.initialHours);
const minutes = ref(props.initialMinutes);
const seconds = ref(props.initialSeconds);
let timerInterval: ReturnType<typeof setInterval> | null = null;

const formatNumber = (num: number): string => String(num).padStart(2, '0');

const startTimer = () => {
    timerInterval = setInterval(() => {
        let h = hours.value;
        let m = minutes.value;
        let s = seconds.value;
        
        s--;
        
        if (s < 0) {
            s = 59;
            m--;
            
            if (m < 0) {
                m = 59;
                h--;
                
                if (h < 0) {
                    h = 23;
                    m = 59;
                    s = 59;
                }
            }
        }
        
        hours.value = h;
        minutes.value = m;
        seconds.value = s;
    }, 1000);
};

onMounted(() => {
    startTimer();
});

onUnmounted(() => {
    if (timerInterval) {
        clearInterval(timerInterval);
    }
});
</script>

<template>
    <section class="Hero">
        <div class="hero-wrapper">
            <div class="hero-text">
                <h2 class="title">Today's Best Deals</h2>
                <p class="description">Fresh offers across all categories. Updated daily</p>
            </div>

            <div class="hero-timer">
                <div class="count-down-timer">
                    <div class="timer">
                        <div class="number">{{ formatNumber(hours) }}</div>
                        <div class="label">HRS</div>
                    </div>
                    <div class="timer-separator">:</div>
                    <div class="timer">
                        <div class="number">{{ formatNumber(minutes) }}</div>
                        <div class="label">MIN</div>
                    </div>
                    <div class="timer-separator">:</div>
                    <div class="timer">
                        <div class="number">{{ formatNumber(seconds) }}</div>
                        <div class="label">SEC</div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>