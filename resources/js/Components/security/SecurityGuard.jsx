import ScreenLockModal from './ScreenLockModal';
import PinSetupModal from './PinSetupModal';

export default function SecurityGuard() {
    return (
        <>
            <PinSetupModal />
            <ScreenLockModal />
        </>
    );
}

