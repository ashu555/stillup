import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';

export default function CreateHeartbeat({ organization, project }) {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        expected_every_seconds: 300,
        grace_seconds: 60,
    });

    const submit = (e) => {
        e.preventDefault();
        post(
            route('organizations.projects.monitors.store-heartbeat', [
                organization.slug,
                project.slug,
            ]),
        );
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Create heartbeat monitor
                </h2>
            }
        >
            <Head title="Create heartbeat monitor" />

            <div className="py-12">
                <div className="mx-auto max-w-2xl sm:px-6 lg:px-8">
                    <p className="mb-4 text-sm text-gray-500">
                        {organization.name} / {project.name}
                    </p>
                    <p className="mb-6 text-sm text-gray-600">
                        Your cron/job will ping a unique URL. If Stillup does not
                        hear from it within the expected interval + grace, the
                        monitor goes down and an incident opens.
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
                                placeholder="Nightly backup job"
                            />
                            <InputError className="mt-2" message={errors.name} />
                        </div>

                        <div className="grid gap-4 sm:grid-cols-2">
                            <div>
                                <InputLabel
                                    htmlFor="expected_every_seconds"
                                    value="Expected every (seconds)"
                                />
                                <TextInput
                                    id="expected_every_seconds"
                                    type="number"
                                    className="mt-1 block w-full"
                                    value={data.expected_every_seconds}
                                    onChange={(e) =>
                                        setData(
                                            'expected_every_seconds',
                                            e.target.value,
                                        )
                                    }
                                    min={60}
                                    max={604800}
                                    required
                                />
                                <p className="mt-1 text-xs text-gray-500">
                                    e.g. 300 = every 5 minutes
                                </p>
                                <InputError
                                    className="mt-2"
                                    message={errors.expected_every_seconds}
                                />
                            </div>

                            <div>
                                <InputLabel
                                    htmlFor="grace_seconds"
                                    value="Grace (seconds)"
                                />
                                <TextInput
                                    id="grace_seconds"
                                    type="number"
                                    className="mt-1 block w-full"
                                    value={data.grace_seconds}
                                    onChange={(e) =>
                                        setData('grace_seconds', e.target.value)
                                    }
                                    min={0}
                                    max={86400}
                                    required
                                />
                                <p className="mt-1 text-xs text-gray-500">
                                    Extra time before marking down
                                </p>
                                <InputError
                                    className="mt-2"
                                    message={errors.grace_seconds}
                                />
                            </div>
                        </div>

                        <div className="flex items-center gap-4">
                            <PrimaryButton disabled={processing}>
                                Create heartbeat
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
