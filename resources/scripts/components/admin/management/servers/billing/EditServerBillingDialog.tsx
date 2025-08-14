import { Server } from '@/api/admin/server';
import updateServer, { Values } from '@/api/admin/servers/updateServer';
import { Product } from '@/api/definitions/admin';
import { Button } from '@/components/elements/button';
import { Dialog } from '@/components/elements/dialog';
import Input from '@/components/elements/Input';
import Label from '@/components/elements/Label';
import { CashIcon, ClockIcon, PencilAltIcon } from '@heroicons/react/outline';
import classNames from 'classnames';
import { Form, Formik } from 'formik';
import { useState } from 'react';

export default ({ server, product }: { server: Server; product?: Product }) => {
    const [open, setOpen] = useState<boolean>(true);

    const [billable, setBillable] = useState<boolean>(Boolean(server.billingProductId));
    const [days, setDays] = useState<number>(server.daysUntilRenewal ?? 0);

    const submit = () => {
        updateServer(server.id, { ...(server as unknown as Partial<Values>), daysUntilRenewal: days })
            .then(() => window.location.reload())
            .catch(error => console.log(error.message));
    };

    return (
        <>
            <Dialog open={open} onClose={() => setOpen(false)} title={'Edit Server Billing'}>
                <Formik onSubmit={() => undefined} initialValues={{}}>
                    <Form>
                        <div className={'grid space-y-6'}>
                            <div>
                                <div className={'flex'}>
                                    <Label>
                                        <CashIcon className={'w-4 inline-flex'} /> Billing Status
                                    </Label>
                                    <span className={'ml-2 italic text-gray-400 text-sm'}>
                                        Should this server be billed automatically?
                                    </span>
                                </div>
                                <button
                                    onClick={() => setBillable(true)}
                                    className={classNames(
                                        billable ? 'bg-black/50' : 'bg-black/25',
                                        'rounded-l py-3 px-6 font-bold text-white',
                                    )}
                                >
                                    Enabled
                                </button>
                                <button
                                    onClick={() => setBillable(false)}
                                    className={classNames(
                                        !billable ? 'bg-black/50' : 'bg-black/25',
                                        'rounded-r py-3 px-6 font-bold text-white',
                                    )}
                                >
                                    Disabled
                                </button>
                            </div>
                            <div>
                                <div className={'flex'}>
                                    <Label>
                                        <ClockIcon className={'w-4 inline-flex'} /> Days until Renewal
                                    </Label>
                                    <span className={'ml-2 italic text-gray-400 text-sm'}>
                                        How many days should there be left?
                                    </span>
                                </div>
                                <Input
                                    defaultValue={days}
                                    type={'number'}
                                    onChange={e => setDays(Number(e.currentTarget.value))}
                                ></Input>
                            </div>
                            <div className={'ml-auto'}>
                                <Button onClick={submit}>Save Changes</Button>
                            </div>
                        </div>
                    </Form>
                </Formik>
            </Dialog>
            <Button size={Button.Sizes.Small} onClick={() => setOpen(true)}>
                Edit <PencilAltIcon className={'ml-1 w-4'} />
            </Button>
        </>
    );
};
