import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Create({ organization, project }) {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        url: 'https://',
        method: 'GET',
        expected_status: 200,
        timeout_seconds: 10,
        interval_seconds: 60,
        keyword: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post(
            route('organizations.projects.monitors.store', [
                organization.slug,
                project.slug,
            ]),
        );
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Create HTTP monitor
                </h2>
            }
        >
            <Head title="Create HTTP monitor" />

            <div className="py-12">
                <div className="mx-auto max-w-2xl sm:px-6 lg:px-8">
                    <p className="mb-4 text-sm text-gray-500">
                        {organization.name} / {project.name}
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
                            <InputLabel htmlFor="url" value="URL" />
                            <TextInput
                                id="url"
                                type="url"
                                className="mt-1 block w-full"
                                value={data.url}
                                onChange={(e) => setData('url', e.target.value)}
                                required
                            />
                            <InputError className="mt-2" message={errors.url} />
                        </div>

                        <div className="grid gap-4 sm:grid-cols-2">
                            <div>
                                <InputLabel htmlFor="method" value="Method" />
                                <select
                                    id="method"
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    value={data.method}
                                    onChange={(e) =>
                                        setData('method', e.target.value)
                                    }
                                >
                                    <option value="GET">GET</option>
                                    <option value="HEAD">HEAD</option>
                                    <option value="POST">POST</option>
                                </select>
                                <InputError
                                    className="mt-2"
                                    message={errors.method}
                                />
                            </div>

                            <div>
                                <InputLabel
                                    htmlFor="expected_status"
                                    value="Expected status"
                                />
                                <TextInput
                                    id="expected_status"
                                    type="number"
                                    className="mt-1 block w-full"
                                    value={data.expected_status}
                                    onChange={(e) =>
                                        setData(
                                            'expected_status',
                                            e.target.value,
                                        )
                                    }
                                    required
                                />
                                <InputError
                                    className="mt-2"
                                    message={errors.expected_status}
                                />
                            </div>
                        </div>

                        <div className="grid gap-4 sm:grid-cols-2">
                            <div>
                                <InputLabel
                                    htmlFor="interval_seconds"
                                    value="Interval (seconds)"
                                />
                                <TextInput
                                    id="interval_seconds"
                                    type="number"
                                    className="mt-1 block w-full"
                                    value={data.interval_seconds}
                                    onChange={(e) =>
                                        setData(
                                            'interval_seconds',
                                            e.target.value,
                                        )
                                    }
                                    min={60}
                                    max={86400}
                                    required
                                />
                                <InputError
                                    className="mt-2"
                                    message={errors.interval_seconds}
                                />
                            </div>

                            <div>
                                <InputLabel
                                    htmlFor="timeout_seconds"
                                    value="Timeout (seconds)"
                                />
                                <TextInput
                                    id="timeout_seconds"
                                    type="number"
                                    className="mt-1 block w-full"
                                    value={data.timeout_seconds}
                                    onChange={(e) =>
                                        setData(
                                            'timeout_seconds',
                                            e.target.value,
                                        )
                                    }
                                    min={1}
                                    max={60}
                                    required
                                />
                                <InputError
                                    className="mt-2"
                                    message={errors.timeout_seconds}
                                />
                            </div>
                        </div>

                        <div>
                            <InputLabel
                                htmlFor="keyword"
                                value="Keyword (optional)"
                            />
                            <TextInput
                                id="keyword"
                                className="mt-1 block w-full"
                                value={data.keyword}
                                onChange={(e) =>
                                    setData('keyword', e.target.value)
                                }
                            />
                            <p className="mt-1 text-xs text-gray-500">
                                If set, the response body must contain this
                                string.
                            </p>
                            <InputError
                                className="mt-2"
                                message={errors.keyword}
                            />
                        </div>

                        <div className="flex items-center gap-4">
                            <PrimaryButton disabled={processing}>
                                Create monitor
                            </PrimaryButton>
                            <Link
                                href={route(
                                    'organizations.projects.monitors.index',
                                    [organization.slug, project.slug],
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
