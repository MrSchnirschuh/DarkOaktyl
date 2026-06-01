const base64UrlDecode = value => {
	const padding = '='.repeat((4 - (value.length % 4)) % 4);
	const normalized = (value + padding).replace(/-/g, '+').replace(/_/g, '/');
	const binary = window.atob(normalized);
	const buffer = new ArrayBuffer(binary.length);
	const bytes = new Uint8Array(buffer);

	for (let i = 0; i < binary.length; i += 1) {
		bytes[i] = binary.charCodeAt(i);
	}

	return buffer;
};

const base64UrlEncode = buffer => {
	const bytes = new Uint8Array(buffer);
	let binary = '';

	bytes.forEach(byte => {
		binary += String.fromCharCode(byte);
	});

	return window
		.btoa(binary)
		.replace(/\+/g, '-')
		.replace(/\//g, '_')
		.replace(/=+$/g, '');
};

const mapCredentialDescriptor = descriptor => ({
	...descriptor,
	id: descriptor.id ? base64UrlDecode(descriptor.id) : descriptor.id,
});

export const prepareRegistrationOptions = options => ({
	...options,
	challenge: options.challenge ? base64UrlDecode(options.challenge) : options.challenge,
	user: {
		...options.user,
		id: options.user?.id ? base64UrlDecode(options.user.id) : options.user?.id,
	},
	excludeCredentials: (options.excludeCredentials || []).map(mapCredentialDescriptor),
});

export const prepareLoginOptions = options => ({
	...options,
	challenge: options.challenge ? base64UrlDecode(options.challenge) : options.challenge,
	allowCredentials: (options.allowCredentials || []).map(mapCredentialDescriptor),
});

const toJSON = value => {
	if (value instanceof ArrayBuffer) {
		return base64UrlEncode(value);
	}

	if (Array.isArray(value)) {
		return value.map(item => toJSON(item));
	}

	if (value && typeof value === 'object') {
		return Object.keys(value).reduce((result, key) => {
			result[key] = toJSON(value[key]);
			return result;
		}, {});
	}

	return value;
};

export const serializePublicKeyCredential = credential => ({
	id: credential.id,
	rawId: base64UrlEncode(credential.rawId),
	type: credential.type,
	response: toJSON(credential.response),
	clientExtensionResults: credential.getClientExtensionResults(),
});

export const isWebAuthnSupported = () => typeof window !== 'undefined' && Boolean(window.PublicKeyCredential);

export const createCredential = async options => {
	if (!isWebAuthnSupported()) {
		throw new Error('WebAuthn is not supported in this browser.');
	}

	const credential = await navigator.credentials.create({
		publicKey: prepareRegistrationOptions(options),
	});

	if (!credential) {
		throw new Error('Registration was cancelled.');
	}

	return serializePublicKeyCredential(credential);
};

export const getAssertion = async options => {
	if (!isWebAuthnSupported()) {
		throw new Error('WebAuthn is not supported in this browser.');
	}

	const credential = await navigator.credentials.get({
		publicKey: prepareLoginOptions(options),
	});

	if (!credential) {
		throw new Error('Authentication was cancelled.');
	}

	return serializePublicKeyCredential(credential);
};

export default {
	prepareRegistrationOptions,
	prepareLoginOptions,
	serializePublicKeyCredential,
	createCredential,
	getAssertion,
	isWebAuthnSupported,
};
