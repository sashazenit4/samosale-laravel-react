import { Head } from '@inertiajs/react';

export default function About() {
    return (
        <div className="container mx-auto p-4">
            <Head title="About Us" />
            <h1 className="mb-4 text-2xl font-bold">About Us</h1>
            <p>This is the About page for our application.</p>
        </div>
    );
}
