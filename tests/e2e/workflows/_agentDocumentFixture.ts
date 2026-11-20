/*
 * SPDX-FileCopyrightText: 2026 Filinq Contributors
 * SPDX-License-Identifier: EUPL-1.2
 *
 * The document the agent-editing workflows seed, shared so the direct-path
 * and open-editor specs edit the same bytes.
 */

/**
 * A minimal but NOT trivial `.docx`, base64-encoded.
 *
 * Embedded rather than committed as a binary, and rather than built with a zip
 * library, so the fixture has no dependency and cannot drift. It deliberately
 * carries a `w:pStyle`, a bold run and a `word/comments.xml` part with a
 * comment range, because the codec's central claim is that everything it did
 * not target survives — a fixture of four bare paragraphs could not catch a
 * codec that silently dropped all of it.
 */
export const FIXTURE_DOCX_B64 = [
	'UEsDBBQAAgAIABwBEF2Rzx8FvQAAACkBAAATAAAAW0NvbnRlbnRfVHlwZXNdLnhtbH2QvQ7CMAyEXyXKiqgLAwNqywCswMAL',
	'WKlbIpofJebv7XEBMTAw2t/d+eRqdXeDulLKNvhaz4pSr5rq+IiUlRCfa31ijkuAbE7kMBchkhfSheSQZUw9RDRn7AnmZbkA',
	'EzyT5ymPGbqpNtThZWC1vcv6fUXsWq3fuvFUrTHGwRpkwTBSaKq9lEq2JXXAxDt0ooJbSC20wVycOIv/MVff/nSdhq6zhr7+',
	'MS2mYChn63s3FF/i0PrJpwe8ntE8AVBLAwQUAAIACAAcARBdXzOVUpUAAAAHAQAACwAAAF9yZWxzLy5yZWxzjc87DsIwDAbg',
	'q0Q+QJ0yMKCmXVi6Ii4QJW5T0TzkhNftycBAEQOjf//6LHfDw6/iRpyXGBS0jYSh70606lKD7JaURW2ErMCVkg6I2TjyOjcx',
	'UaibKbLXpY48Y9LmomfCnZR75E8DtqYYrQIebQvi/Ez0jx2naTF0jObqKZQfJ74aVdY8U1Fwj2zRvuOmsoB9h5sX+xdQSwME',
	'FAACAAgAHAEQXW9FqKocAQAAEQIAABEAAAB3b3JkL2RvY3VtZW50LnhtbHWRzW7CMAzHX8XKeSOMwzRVtJzYZZdpsAcwjWkj',
	'mg/ZgcLbL6WMgcQudiz7//NH5ouj6+BALDb4Ur1MpgrI18FY35Tqe/3+/KZAEnqDXfBUqhOJWlTzvjCh3jvyCTLAS9GXqk0p',
	'FlpL3ZJDmYRIPue2gR2mHHKj+8AmcqhJJPNdp2fT6at2aL0akJtgToOPZ/PJZ7dKp46gLw7YlWptU0dKV3N9LRjN+N6MmUuU',
	'quVsCVt7THsmaAmHpYZ8GqtGyrXhRbNuCVAkj3jeLhLbYMAKkG3aBD3RTib/U+rgBt0X+oZWCTkrCmvyZYepb5pkYETGhjG2',
	'UCOzJQGEixx40N+3uWcvvbklP9rkw+YapgbZyNODifXvxfXfb1Y/UEsDBBQAAgAIABwBEF0B/c27YAAAAI0AAAARAAAAd29y',
	'ZC9jb21tZW50cy54bWxNzEsOgCAMBNCrEA6AuiV87uAVtAsSKaQg9fgKccHmpZnJ1PgnXqIBlZDQyk2t0jvD+kgxAtYivhqL',
	'ZitvQs1y6gTrcH6TkeUOdarboQVgIIGpgjJLz7o0zMP/x3wX9wJQSwECPwMUAAIACAAcARBdkc8fBb0AAAApAQAAEwAAAAAA',
	'AAAAAAAAtoEAAAAAW0NvbnRlbnRfVHlwZXNdLnhtbFBLAQI/AxQAAgAIABwBEF1fM5VSlQAAAAcBAAALAAAAAAAAAAAAAAC2',
	'ge4AAABfcmVscy8ucmVsc1BLAQI/AxQAAgAIABwBEF1vRaiqHAEAABECAAARAAAAAAAAAAAAAAC2gawBAAB3b3JkL2RvY3Vt',
	'ZW50LnhtbFBLAQI/AxQAAgAIABwBEF0B/c27YAAAAI0AAAARAAAAAAAAAAAAAAC2gfcCAAB3b3JkL2NvbW1lbnRzLnhtbFBL',
	'BQYAAAAABAAEAPgAAACGAwAAAAA=',
].join('')
