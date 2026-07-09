import Checkbox from '@/Components/Checkbox';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Create({ organization }) {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        slug: '',
        public_status_enabled: false,
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('organizations.projects.store', organization.slug));
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Create project
                </h2>
            }
        >
            <Head title="Create project" />

            <div className="py-12">
                <div className="mx-auto max-w-2xl sm:px-6 lg:px-8">
                    <p className="mb-4 text-sm text-gray-500">
                        Organization: {organization.name}
                    </p>

                    <form
                        onSubmit={submit}
                        className="space-y-6 bg-white p-6 shadow-sm sm:rounded-lg"
                    >
                        <div>
                            <InputLabel htmlFor="name" value="Name" />
                            <TextInput
                                id="name"
                                className="mt-1 block w-full"
                                value={data.name}
                                onChange={(e) => setData('name', e.target.value)}
                                required
                                isFocused
                            />
                            <InputError className="mt-2" message={errors.name} />
                        </div>

                        <div>
                            <InputLabel htmlFor="slug" value="Slug (optional)" />
                            <TextInput
                                id="slug"
                                className="mt-1 block w-full"
                                value={data.slug}
                                onChange={(e) => setData('slug', e.target.value)}
                            />
                            <InputError className="mt-2" message={errors.slug} />
                        </div>

                        <div className="flex items-center gap-2">
                            <Checkbox
                                id="public_status_enabled"
                                checked={data.public_status_enabled}
                                onChange={(e) =>
                                    setData(
                                        'public_status_enabled',
                                        e.target.checked,
                                    )
                                }
                            />
                            <InputLabel
                                htmlFor="public_status_enabled"
                                value="Enable public status page"
                                className="!mb-0"
                            />
                        </div>
                        <InputError
                            className="mt-2"
                            message={errors.public_status_enabled}
                        />

                        <div className="flex items-center gap-4">
                            <PrimaryButton disabled={processing}>
                                Create
                            </PrimaryButton>
                            <Link
                                href={route(
                                    'organizations.show',
                                    organization.slug,
                                )}
                                className="text-sm text-gray-600 hover:text-gray-900"
                            >
                                Cancel
                            </Link>
                        </div>
                    </form>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
